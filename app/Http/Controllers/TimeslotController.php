<?php

namespace App\Http\Controllers;

use App\Models\Timeslot;
use Illuminate\Http\Request;

class TimeslotController extends Controller
{
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
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

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
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

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
