<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Services\SchedulingConstraintService;
use App\Services\SchedulingTimePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventScheduleController extends Controller
{
    public function __construct(
        private readonly SchedulingConstraintService $constraints,
        private readonly SchedulingTimePolicy $timePolicy
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schedules = EventSchedule::with(['event', 'venue', 'timeslot'])->latest()->get();

        return view('schedules.index', compact('schedules')); //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $events = Event::orderBy('title')->get();
        $venues = Venue::orderBy('name')->get();

        return view('schedules.create', compact('events', 'venues')); //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'venue_id' => 'required|exists:venues,id',
            'timeslot_id' => 'nullable|exists:timeslots,id',
            'slot_date' => 'required_without:timeslot_id|date',
            'start_time' => 'required_without:timeslot_id|date_format:H:i',
            'end_time' => 'required_without:timeslot_id|date_format:H:i|after:start_time',
            'status' => 'nullable|in:manual',
        ]);

        $event = Event::findOrFail($validated['event_id']);
        $venue = Venue::findOrFail($validated['venue_id']);
        DB::transaction(function () use ($validated, $event, $venue): void {
            $timeslot = $this->resolveTimeslot($validated, $event);
            $this->constraints->validate($event, $venue, $timeslot);

            EventSchedule::create([
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'timeslot_id' => $timeslot->id,
                'status' => 'manual',
            ]);
            if ($event->status === EventStatus::Approved) {
                $event->update(['status' => EventStatus::Scheduled]);
            }
        });

        return redirect()->route('schedules.index')->with('success', 'Schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EventSchedule $schedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventSchedule $schedule)
    {
        abort_if($schedule->event->status === EventStatus::Published, 422, 'Unpublish the event before changing its schedule.');
        $events = Event::orderBy('title')->get();
        $venues = Venue::orderBy('name')->get();

        return view('schedules.edit', compact('schedule', 'events', 'venues')); //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EventSchedule $schedule)
    {
        abort_if($schedule->event->status === EventStatus::Published, 422, 'Unpublish the event before changing its schedule.');
        $previousEvent = $schedule->event;
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'venue_id' => 'required|exists:venues,id',
            'timeslot_id' => 'nullable|exists:timeslots,id',
            'slot_date' => 'required_without:timeslot_id|date',
            'start_time' => 'required_without:timeslot_id|date_format:H:i',
            'end_time' => 'required_without:timeslot_id|date_format:H:i|after:start_time',
            'status' => 'nullable|in:manual',
        ]);

        $event = Event::findOrFail($validated['event_id']);
        $venue = Venue::findOrFail($validated['venue_id']);
        DB::transaction(function () use ($validated, $event, $venue, $schedule, $previousEvent): void {
            $timeslot = $this->resolveTimeslot($validated, $event);
            $this->constraints->validate($event, $venue, $timeslot, $schedule);

            $schedule->update([
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'timeslot_id' => $timeslot->id,
                'status' => 'manual',
            ]);
            if ($previousEvent->id !== $event->id && $previousEvent->status === EventStatus::Scheduled) {
                $previousEvent->update(['status' => EventStatus::Approved]);
            }
            if ($event->status === EventStatus::Approved) {
                $event->update(['status' => EventStatus::Scheduled]);
            }
        });

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventSchedule $schedule)
    {
        abort_if($schedule->event->status === EventStatus::Published, 422, 'Unpublish the event before deleting its schedule.');
        $event = $schedule->event;
        $schedule->delete();
        if ($event->status === EventStatus::Scheduled) {
            $event->update(['status' => EventStatus::Approved]);
        }

        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully.'); //
    }

    private function resolveTimeslot(array $validated, Event $event): Timeslot
    {
        if (! empty($validated['timeslot_id'])) {
            return Timeslot::findOrFail($validated['timeslot_id']);
        }

        $this->timePolicy->validate(
            $validated['slot_date'],
            $validated['start_time'],
            $validated['end_time'],
            $event->is_outside_working_hours
        );

        return Timeslot::query()
            ->whereDate('slot_date', $validated['slot_date'])
            ->where('start_time', $validated['start_time'].':00')
            ->where('end_time', $validated['end_time'].':00')
            ->first() ?? Timeslot::create([
                'slot_date' => $validated['slot_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ]);
    }
}
