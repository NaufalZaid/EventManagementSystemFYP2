<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceHistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $records = $request->user()->attendanceRecords()
            ->with(['event.organizer', 'event.schedules.venue', 'event.schedules.timeslot'])
            ->latest('checked_in_at')->get();

        return view('attendance.history', compact('records'));
    }
}
