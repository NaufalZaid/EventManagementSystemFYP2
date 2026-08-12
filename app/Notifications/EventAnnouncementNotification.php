<?php

namespace App\Notifications;

use App\Models\EventAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventAnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly EventAnnouncement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'announcement',
            'title' => $this->announcement->title,
            'message' => $this->announcement->message,
            'event_id' => $this->announcement->event_id,
            'event_title' => $this->announcement->event->title,
            'url' => route('discover.show', $this->announcement->event_id),
        ];
    }
}
