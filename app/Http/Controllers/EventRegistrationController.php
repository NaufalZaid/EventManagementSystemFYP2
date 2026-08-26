<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\CalendarConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventRegistrationController extends Controller
{
    public function __construct(private readonly CalendarConflictService $conflicts) {}

    public function index(Request $request)
    {
        $registrations = $request->user()->eventRegistrations()
            ->with(['event.organizer', 'event.schedules.venue', 'event.schedules.timeslot'])
            ->where('status', RegistrationStatus::Registered)
            ->get()
            ->sortBy(fn (EventRegistration $registration) => $registration->event->schedules->first()?->timeslot?->slot_date?->format('Y-m-d').' '.$registration->event->schedules->first()?->timeslot?->start_time);

        return view('registrations.index', compact('registrations'));
    }

    public function store(Request $request, Event $event)
    {
        DB::transaction(function () use ($request, $event): void {
            $event = Event::with('schedules.timeslot')->lockForUpdate()->findOrFail($event->id);
            abort_unless($event->status === EventStatus::Published, 422, 'This event is not open for registration.');
            $schedule = $event->schedules->first();
            abort_unless($schedule, 422, 'This event does not have a schedule.');

            $registration = EventRegistration::where('event_id', $event->id)
                ->where('user_id', $request->user()->id)->lockForUpdate()->first();
            if ($registration?->status === RegistrationStatus::Registered) {
                throw ValidationException::withMessages(['event' => 'You are already registered for this event.']);
            }

            $registeredCount = EventRegistration::where('event_id', $event->id)
                ->where('status', RegistrationStatus::Registered)->count();
            if ($registeredCount >= $event->capacity) {
                throw ValidationException::withMessages(['event' => 'This event has reached its registration capacity.']);
            }

            $timeslot = $schedule->timeslot;
            $startsAt = $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->start_time);
            $endsAt = $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->end_time);
            $conflicts = $this->conflicts->conflicts($request->user(), $startsAt, $endsAt, exceptEvent: $event);
            if ($conflicts !== []) {
                throw ValidationException::withMessages([
                    'event' => 'This event clashes with '.implode(', ', $conflicts).'.',
                ]);
            }

            EventRegistration::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $request->user()->id],
                ['status' => RegistrationStatus::Registered, 'registered_at' => now(), 'cancelled_at' => null]
            );
        });

        return redirect()->route('my-events.index')->with('success', 'Registration confirmed. The event is now in My Events.');
    }

    public function destroy(Request $request, Event $event)
    {
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->where('status', RegistrationStatus::Registered)
            ->firstOrFail();
        if ($registration->attendanceRecord()->exists()) {
            return back()->with('warning', 'You cannot cancel after attendance has been recorded.');
        }
        $registration->update(['status' => RegistrationStatus::Cancelled, 'cancelled_at' => now()]);

        return back()->with('success', 'Event registration cancelled.');
    }
}
