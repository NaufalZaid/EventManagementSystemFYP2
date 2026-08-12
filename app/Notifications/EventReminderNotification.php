<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Event $event, private readonly int $leadMinutes) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $schedule = $this->event->schedules->first();

        return [
            'kind' => 'reminder',
            'title' => $this->label().' reminder',
            'message' => $this->event->title.' starts on '.$schedule->timeslot->slot_date->format('d M Y').' at '.substr($schedule->timeslot->start_time, 0, 5).'.',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'lead_minutes' => $this->leadMinutes,
            'url' => route('discover.show', $this->event),
        ];
    }

    private function label(): string
    {
        return match ($this->leadMinutes) {
            10080 => 'One week',
            1440 => 'One day',
            60 => 'One hour',
            default => $this->leadMinutes.' minute',
        };
    }
}
