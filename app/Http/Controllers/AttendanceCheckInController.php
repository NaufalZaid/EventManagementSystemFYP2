<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCheckInController extends Controller
{
    public function show(Request $request, string $token)
    {
        $session = $this->session($token);
        $registration = EventRegistration::where('event_id', $session->event_id)
            ->where('user_id', $request->user()->id)->where('status', RegistrationStatus::Registered)->first();
        $alreadyCheckedIn = $registration?->attendanceRecord()->exists() ?? false;

        return view('attendance.check-in', compact('session', 'registration', 'alreadyCheckedIn', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $session = $this->session($token);

        DB::transaction(function () use ($request, $session): void {
            $registration = EventRegistration::where('event_id', $session->event_id)
                ->where('user_id', $request->user()->id)
                ->where('status', RegistrationStatus::Registered)
                ->lockForUpdate()->first();
            if (! $registration) {
                throw ValidationException::withMessages(['attendance' => 'You must have an active registration for this event.']);
            }
            if (AttendanceRecord::where('event_registration_id', $registration->id)->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Your attendance has already been recorded.']);
            }
            AttendanceRecord::create([
                'event_id' => $session->event_id,
                'attendance_session_id' => $session->id,
                'event_registration_id' => $registration->id,
                'user_id' => $request->user()->id,
                'method' => 'qr',
                'checked_in_at' => now(),
            ]);
        });

        return redirect()->route('attendance.history')->with('success', 'Attendance recorded successfully.');
    }

    private function session(string $token): AttendanceSession
    {
        $session = AttendanceSession::with(['event.schedules.venue', 'event.schedules.timeslot'])
            ->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($session->isOpen(), 410, 'This attendance QR code has expired or been closed.');

        return $session;
    }
}
