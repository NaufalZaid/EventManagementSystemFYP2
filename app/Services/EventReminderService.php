<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\EventRegistration;
use App\Models\ReminderDelivery;
use App\Notifications\EventReminderNotification;
use Carbon\Carbon;

class EventReminderService
{
    public const LEAD_MINUTES = [10080, 1440, 60];

    public function sendDue(): int
    {
        $sent = 0;
        $registrations = EventRegistration::query()
            ->where('status', RegistrationStatus::Registered)
            ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Published))
            ->with(['user', 'event.schedules.timeslot'])
            ->get();

        foreach ($registrations as $registration) {
            $timeslot = $registration->event->schedules->first()?->timeslot;
            if (! $timeslot) {
                continue;
            }
            $startsAt = Carbon::parse($timeslot->slot_date->format('Y-m-d').' '.$timeslot->start_time);
            if ($startsAt->isPast()) {
                continue;
            }

            foreach (self::LEAD_MINUTES as $leadMinutes) {
                if (now()->lt($startsAt->copy()->subMinutes($leadMinutes))) {
                    continue;
                }
                $delivery = ReminderDelivery::firstOrCreate([
                    'event_registration_id' => $registration->id,
                    'lead_minutes' => $leadMinutes,
                ]);
                if ($delivery->sent_at) {
                    continue;
                }
                $registration->user->notify(new EventReminderNotification($registration->event, $leadMinutes));
                $delivery->update(['sent_at' => now()]);
                $sent++;
            }
        }

        return $sent;
    }
}
