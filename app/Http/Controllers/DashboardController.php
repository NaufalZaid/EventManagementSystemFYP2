<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Enums\VenueRequestStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventTask;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return match ($request->user()->role) {
            UserRole::Student => view('dashboards.student', [
                'registeredCount' => $request->user()->eventRegistrations()->where('status', RegistrationStatus::Registered)->count(),
                'publishedCount' => Event::where('status', EventStatus::Published)->count(),
                'commitmentCount' => $request->user()->personalCommitments()->count(),
                'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
                'nextRegistration' => $request->user()->eventRegistrations()
                    ->where('status', RegistrationStatus::Registered)
                    ->with(['event.schedules.venue', 'event.schedules.timeslot'])
                    ->get()
                    ->sortBy(fn ($registration) => $registration->event->schedules->first()?->timeslot?->slot_date?->format('Y-m-d').' '.$registration->event->schedules->first()?->timeslot?->start_time)
                    ->first(),
            ]),
            UserRole::Organizer => view('dashboards.organizer', [
                'eventCount' => Event::accessibleTo($request->user())->count(),
                'scheduledCount' => Event::accessibleTo($request->user())->where('status', EventStatus::Scheduled)->count(),
                'pendingCount' => Event::accessibleTo($request->user())->where('status', EventStatus::Submitted)->count(),
                'openTaskCount' => EventTask::whereHas('event', fn ($query) => $query->accessibleTo($request->user()))->whereNull('completed_at')->count(),
            ]),
            UserRole::Administrator => view('dashboards.administrator', [
                'userCount' => User::count(),
                'eventCount' => Event::count(),
                'venueCount' => Venue::count(),
                'timeslotCount' => Timeslot::count(),
                'scheduleCount' => EventSchedule::count(),
                'proposalCount' => Event::where('status', EventStatus::Submitted)->count(),
                'venueRequestCount' => VenueRequest::where('status', VenueRequestStatus::Pending)->count(),
            ]),
        };
    }
}
