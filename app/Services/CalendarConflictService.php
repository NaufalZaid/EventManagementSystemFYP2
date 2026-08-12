<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\PersonalCommitment;
use App\Models\User;
use Carbon\CarbonInterface;

class CalendarConflictService
{
    /** @return array<int, string> */
    public function conflicts(
        User $user,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?PersonalCommitment $exceptCommitment = null,
        ?Event $exceptEvent = null,
    ): array {
        $conflicts = [];
        $registrations = $user->eventRegistrations()
            ->where('status', RegistrationStatus::Registered)
            ->when($exceptEvent, fn ($query) => $query->where('event_id', '!=', $exceptEvent->id))
            ->with('event.schedules.timeslot')->get();

        foreach ($registrations as $registration) {
            $timeslot = $registration->event->schedules->first()?->timeslot;
            if (! $timeslot) {
                continue;
            }
            $eventStart = $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->start_time);
            $eventEnd = $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->end_time);
            if ($startsAt->lessThan($eventEnd) && $endsAt->greaterThan($eventStart)) {
                $conflicts[] = 'Registered event: '.$registration->event->title;
            }
        }

        $commitments = $user->personalCommitments()
            ->when($exceptCommitment, fn ($query) => $query->where('id', '!=', $exceptCommitment->id))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->get();

        foreach ($commitments as $commitment) {
            $conflicts[] = 'Personal commitment: '.$commitment->title;
        }

        return $conflicts;
    }
}
