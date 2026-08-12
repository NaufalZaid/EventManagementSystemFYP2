<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Services\SchedulingConstraintService;
use Illuminate\Http\Request;

class EventScheduleController extends Controller
{
    public function __construct(private readonly SchedulingConstraintService $constraints) {}

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
        $timeslots = Timeslot::orderBy('slot_date')->orderBy('start_time')->get();

        return view('schedules.create', compact('events', 'venues', 'timeslots')); //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'venue_id' => 'required|exists:venues,id',
            'timeslot_id' => 'required|exists:timeslots,id',
            'status' => 'required|string|max:50',
        ]);

        $event = Event::findOrFail($validated['event_id']);
        $venue = Venue::findOrFail($validated['venue_id']);
        $timeslot = Timeslot::findOrFail($validated['timeslot_id']);
        $this->constraints->validate($event, $venue, $timeslot);

        EventSchedule::create($validated);
        if ($event->status === EventStatus::Approved) {
            $event->update(['status' => EventStatus::Scheduled]);
        }

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
        $timeslots = Timeslot::orderBy('slot_date')->orderBy('start_time')->get();

        return view('schedules.edit', compact('schedule', 'events', 'venues', 'timeslots')); //
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
            'timeslot_id' => 'required|exists:timeslots,id',
            'status' => 'required|string|max:50',
        ]);

        $event = Event::findOrFail($validated['event_id']);
        $venue = Venue::findOrFail($validated['venue_id']);
        $timeslot = Timeslot::findOrFail($validated['timeslot_id']);
        $this->constraints->validate($event, $venue, $timeslot, $schedule);

        $schedule->update($validated);
        if ($previousEvent->id !== $event->id && $previousEvent->status === EventStatus::Scheduled) {
            $previousEvent->update(['status' => EventStatus::Approved]);
        }
        if ($event->status === EventStatus::Approved) {
            $event->update(['status' => EventStatus::Scheduled]);
        }

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
}
