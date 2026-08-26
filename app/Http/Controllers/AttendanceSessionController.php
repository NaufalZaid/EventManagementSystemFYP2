<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceSessionController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodes) {}

    public function show(Request $request, Event $event)
    {
        EventPlanningController::authorizeEvent($request, $event);
        $event->load(['schedules.venue', 'schedules.timeslot'])
            ->loadCount([
                'registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered),
                'attendanceRecords as attended_count',
            ]);
        $session = $event->attendanceSessions()->latest()->first();
        $registrations = $event->registrations()->where('status', RegistrationStatus::Registered)
            ->with(['user', 'attendanceRecord'])->orderBy('registered_at')->get();
        $qrCode = $session?->isOpen()
            ? $this->qrCodes->dataUri(route('attendance.check-in.show', $session->token))
            : null;

        return view('attendance.manage', compact('event', 'session', 'registrations', 'qrCode'));
    }

    public function store(Request $request, Event $event)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($event->status === EventStatus::Published, 422, 'Only published events can open attendance.');
        abort_unless($event->schedules()->exists(), 422, 'The event requires a schedule.');
        abort_if($event->attendanceSessions()->whereNull('closed_at')->where('closes_at', '>', now())->exists(), 422, 'An attendance session is already active.');
        $validated = $request->validate(['duration_minutes' => ['required', 'integer', 'min:5', 'max:180']]);
        $token = Str::random(64);
        $event->attendanceSessions()->create([
            'created_by' => $request->user()->id,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'opens_at' => now(),
            'closes_at' => now()->addMinutes((int) $validated['duration_minutes']),
        ]);

        return back()->with('success', 'Attendance QR session opened.');
    }

    public function close(Request $request, Event $event, AttendanceSession $session)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($session->event_id === $event->id, 404);
        $session->update(['closed_at' => now()]);

        return back()->with('success', 'Attendance session closed.');
    }

    public function manual(Request $request, Event $event, AttendanceSession $session)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($session->event_id === $event->id && $session->isOpen(), 422, 'The attendance session is not active.');
        $validated = $request->validate(['registration_id' => ['required', 'exists:event_registrations,id']]);

        DB::transaction(function () use ($request, $event, $session, $validated): void {
            $registration = EventRegistration::lockForUpdate()->findOrFail($validated['registration_id']);
            abort_unless($registration->event_id === $event->id && $registration->status === RegistrationStatus::Registered, 422, 'The student does not have an active registration.');
            if (AttendanceRecord::where('event_id', $event->id)->where('user_id', $registration->user_id)->exists()) {
                throw ValidationException::withMessages(['registration_id' => 'Attendance has already been recorded for this student.']);
            }
            AttendanceRecord::create([
                'event_id' => $event->id,
                'attendance_session_id' => $session->id,
                'event_registration_id' => $registration->id,
                'user_id' => $registration->user_id,
                'recorded_by' => $request->user()->id,
                'method' => 'manual',
                'checked_in_at' => now(),
            ]);
        });

        return back()->with('success', 'Student attendance recorded manually.');
    }
}
