<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Venue;
use App\Models\Timeslot;
use App\Models\EventSchedule;
use Illuminate\Http\Request;

class EventScheduleController extends Controller
{
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
     
         if ($event->capacity > $venue->capacity) {
             return back()
                 ->withErrors(['venue_id' => 'Selected venue capacity is too small for this event.'])
                 ->withInput();
         }
     
         $eventConflict = EventSchedule::where('event_id', $validated['event_id'])->exists();
     
         if ($eventConflict) {
             return back()
                 ->withErrors(['event_id' => 'This event has already been scheduled.'])
                 ->withInput();
         }
     
         $existingSchedules = EventSchedule::with('timeslot')
             ->where('venue_id', $validated['venue_id'])
             ->get();
     
         foreach ($existingSchedules as $schedule) {
             $existing = $schedule->timeslot;
     
             $sameDate = $existing->slot_date === $timeslot->slot_date;
             $overlap = $timeslot->start_time < $existing->end_time
                 && $timeslot->end_time > $existing->start_time;
     
             if ($sameDate && $overlap) {
                 return back()
                     ->withErrors(['venue_id' => 'This venue already has another event during the selected time range.'])
                     ->withInput();
             }
         }
     
         EventSchedule::create($validated);
     
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
         $validated = $request->validate([
             'event_id' => 'required|exists:events,id',
             'venue_id' => 'required|exists:venues,id',
             'timeslot_id' => 'required|exists:timeslots,id',
             'status' => 'required|string|max:50',
         ]);
     
         $event = Event::findOrFail($validated['event_id']);
         $venue = Venue::findOrFail($validated['venue_id']);
         $timeslot = Timeslot::findOrFail($validated['timeslot_id']);
     
         if ($event->capacity > $venue->capacity) {
             return back()
                 ->withErrors(['venue_id' => 'Selected venue capacity is too small for this event.'])
                 ->withInput();
         }
     
         $eventConflict = EventSchedule::where('event_id', $validated['event_id'])
             ->where('id', '!=', $schedule->id)
             ->exists();
     
         if ($eventConflict) {
             return back()
                 ->withErrors(['event_id' => 'This event has already been scheduled.'])
                 ->withInput();
         }
     
         $existingSchedules = EventSchedule::with('timeslot')
             ->where('venue_id', $validated['venue_id'])
             ->where('id', '!=', $schedule->id)
             ->get();
     
         foreach ($existingSchedules as $existingSchedule) {
             $existing = $existingSchedule->timeslot;
     
             $sameDate = $existing->slot_date === $timeslot->slot_date;
             $overlap = $timeslot->start_time < $existing->end_time
                 && $timeslot->end_time > $existing->start_time;
     
             if ($sameDate && $overlap) {
                 return back()
                     ->withErrors(['venue_id' => 'This venue already has another event during the selected time range.'])
                     ->withInput();
             }
         }
     
         $schedule->update($validated);
     
         return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
     }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully.'); //
    }
}
