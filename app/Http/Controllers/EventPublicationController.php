<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;

class EventPublicationController extends Controller
{
    public function publish(Event $event)
    {
        abort_unless($event->status === EventStatus::Scheduled, 422, 'Only scheduled events can be published.');
        abort_unless($event->schedules()->exists(), 422, 'The event needs a schedule before publication.');
        $event->update(['status' => EventStatus::Published]);

        return back()->with('success', 'Event published to the student catalogue.');
    }

    public function unpublish(Event $event)
    {
        abort_unless($event->status === EventStatus::Published, 422, 'Only published events can be unpublished.');
        $event->update(['status' => EventStatus::Scheduled]);

        return back()->with('success', 'Event removed from discovery. Existing registrations were preserved.');
    }
}
