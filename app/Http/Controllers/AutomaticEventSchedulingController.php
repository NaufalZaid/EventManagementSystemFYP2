<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Services\SchedulingTimePolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutomaticEventSchedulingController extends Controller
{
    public function __construct(private readonly SchedulingTimePolicy $timePolicy) {}

    public function create(Request $request, Event $event)
    {
        $this->authorizeAllocation($request, $event);

        return view('events.allocate', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        $this->authorizeAllocation($request, $event);
        $validated = $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:60', 'max:900', 'multiple_of:60'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        $startsAt = Carbon::parse($validated['slot_date'].' '.$validated['start_time']);
        $endsAt = $startsAt->copy()->addMinutes((int) $validated['duration_minutes']);
        $usesExtendedHours = $endsAt->hour > SchedulingTimePolicy::NORMAL_CLOSING_HOUR;

        $this->timePolicy->validate(
            $validated['slot_date'],
            $validated['start_time'],
            $endsAt->format('H:i'),
            $usesExtendedHours
        );

        DB::transaction(function () use ($request, $event, $validated, $startsAt, $endsAt, $usesExtendedHours): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            $this->authorizeAllocation($request, $event);

            $venue = Venue::query()
                ->where('is_active', true)
                ->where('capacity', '>=', $validated['capacity'])
                ->whereDoesntHave('schedules', function ($query) use ($validated, $endsAt): void {
                    $query->whereHas('timeslot', function ($query) use ($validated, $endsAt): void {
                        $query->whereDate('slot_date', $validated['slot_date'])
                            ->where('start_time', '<', $endsAt->format('H:i:s'))
                            ->where('end_time', '>', $validated['start_time'].':00');
                    });
                })
                ->whereDoesntHave('blackouts', function ($query) use ($startsAt, $endsAt): void {
                    $query->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                })
                ->orderBy('capacity')
                ->orderBy('name')
                ->lockForUpdate()
                ->first();

            if (! $venue) {
                throw ValidationException::withMessages([
                    'capacity' => 'No active venue has enough capacity and availability for the selected date and time.',
                ]);
            }

            $timeslot = Timeslot::firstOrCreate([
                'slot_date' => $validated['slot_date'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $endsAt->format('H:i:s'),
            ]);

            EventSchedule::create([
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'timeslot_id' => $timeslot->id,
                'status' => 'generated',
            ]);

            $event->update([
                'capacity' => $validated['capacity'],
                'duration_minutes' => $validated['duration_minutes'],
                'is_outside_working_hours' => $usesExtendedHours,
                'preferred_venue_id' => $venue->id,
                'preferred_date' => $validated['slot_date'],
                'preferred_start_time' => $validated['start_time'],
                'status' => EventStatus::Published,
            ]);
        });

        return redirect()->route('events.index')->with('success', 'A suitable venue was allocated automatically and the event is now published.');
    }

    private function authorizeAllocation(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageEvent($event), 403);
        abort_unless($event->status === EventStatus::Approved, 422, 'Only an approved, unscheduled event can be allocated automatically.');
        abort_if($event->schedules()->exists(), 422, 'This event has already been scheduled.');
    }
}
