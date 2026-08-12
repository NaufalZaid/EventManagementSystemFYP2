<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Venue;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->with(['organizer', 'schedules.venue', 'schedules.timeslot'])
            ->withCount([
                'registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered),
                'attendanceRecords as attended_count',
            ])
            ->when($request->user()->hasRole('organizer'), fn ($query) => $query->where('organizer_id', $request->user()->id))
            ->whereHas('schedules')
            ->latest()->get();
        $eventIds = $events->pluck('id');
        $totalRegistrations = EventRegistration::whereIn('event_id', $eventIds)->where('status', RegistrationStatus::Registered)->count();
        $totalAttendance = AttendanceRecord::whereIn('event_id', $eventIds)->count();
        $venues = $request->user()->hasRole('administrator')
            ? Venue::withCount('schedules')->orderByDesc('schedules_count')->take(8)->get()
            : collect();

        return view('analytics.index', compact('events', 'totalRegistrations', 'totalAttendance', 'venues'));
    }
}
