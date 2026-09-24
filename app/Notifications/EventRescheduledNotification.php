<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventRescheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly array $oldSchedule,
        private readonly array $newSchedule,
        private readonly string $blackoutReason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'event_rescheduled',
            'title' => 'Event automatically rescheduled',
            'message' => $this->event->title.' was moved from '.$this->description($this->oldSchedule)
                .' to '.$this->description($this->newSchedule).' because of a venue blackout: '.$this->blackoutReason.'.',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'old_schedule' => $this->oldSchedule,
            'new_schedule' => $this->newSchedule,
            'url' => route('events.index'),
        ];
    }

    private function description(array $schedule): string
    {
        return $schedule['date'].' '.$schedule['start'].'–'.$schedule['end'].' at '.$schedule['venue'];
    }
}
