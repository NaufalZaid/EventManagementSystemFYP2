<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAnnouncement;
use App\Notifications\EventAnnouncementNotification;
use Illuminate\Http\Request;

class EventAnnouncementController extends Controller
{
    public function store(Request $request, Event $event)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($event->status === EventStatus::Published, 422, 'Publish the event before sending announcements.');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        $announcement = $event->announcements()->create($validated + [
            'created_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        $registrations = $event->registeredParticipants()->with('user')->get();
        foreach ($registrations as $registration) {
            $registration->user->notify(new EventAnnouncementNotification($announcement));
        }

        return back()->with('success', 'Announcement sent to '.$registrations->count().' registered student(s).');
    }

    public function destroy(Request $request, Event $event, EventAnnouncement $announcement)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($announcement->event_id === $event->id, 404);
        $announcement->delete();

        return back()->with('success', 'Announcement removed from the planning history. Delivered notifications remain available to students.');
    }
}
