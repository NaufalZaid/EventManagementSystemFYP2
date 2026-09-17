<?php

namespace App\Http\Controllers;

use App\Models\Timeslot;
use App\Services\SchedulingTimePolicy;
use Illuminate\Http\Request;

class TimeslotController extends Controller
{
    public function __construct(private readonly SchedulingTimePolicy $timePolicy) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timeslots = Timeslot::latest()->get();

        return view('timeslots.index', compact('timeslots')); //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('timeslots.create'); //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slot_date' => 'required|date',
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_outside_working_hours' => ['nullable', 'boolean'],
        ]);
        $this->timePolicy->validate($validated['slot_date'], $validated['start_time'], $validated['end_time'], $request->boolean('is_outside_working_hours'));
        unset($validated['is_outside_working_hours']);

        Timeslot::create($validated);

        return redirect()->route('timeslots.index')->with('success', 'Timeslot created successfully.'); //
    }

    /**
     * Display the specified resource.
     */
    public function show(Timeslot $timeslot)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Timeslot $timeslot)
    {
        return view('timeslots.edit', compact('timeslot')); //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timeslot $timeslot)
    {
        $validated = $request->validate([
            'slot_date' => 'required|date',
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_outside_working_hours' => ['nullable', 'boolean'],
        ]);
        $this->timePolicy->validate($validated['slot_date'], $validated['start_time'], $validated['end_time'], $request->boolean('is_outside_working_hours'));
        unset($validated['is_outside_working_hours']);

        $timeslot->update($validated);

        return redirect()->route('timeslots.index')->with('success', 'Timeslot updated successfully.'); //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timeslot $timeslot)
    {
        $timeslot->delete();

        return redirect()->route('timeslots.index')->with('success', 'Timeslot deleted successfully.'); //
    }
}
