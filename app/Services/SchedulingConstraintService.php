<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Models\VenueBlackout;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SchedulingConstraintService
{
    public function validate(Event $event, Venue $venue, Timeslot $timeslot, ?EventSchedule $except = null): void
    {
        $errors = [];
        [$startsAt, $endsAt] = $this->boundaries($timeslot);

        if (! $venue->is_active) {
            $errors['venue_id'] = 'The selected venue is currently inactive.';
        } elseif ($event->capacity > $venue->capacity) {
            $errors['venue_id'] = 'The selected venue capacity is too small for this event.';
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $errors['timeslot_id'] = 'The timeslot end time must be after its start time.';
        } elseif ($event->duration_minutes && $startsAt->diffInMinutes($endsAt) < $event->duration_minutes) {
            $errors['timeslot_id'] = 'The selected timeslot is shorter than the event duration.';
        }

        $eventSchedule = EventSchedule::where('event_id', $event->id)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists();

        if ($eventSchedule) {
            $errors['event_id'] = 'This event has already been scheduled.';
        }

        $venueConflict = EventSchedule::query()
            ->where('venue_id', $venue->id)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->whereHas('timeslot', function ($query) use ($timeslot): void {
                $query->whereDate('slot_date', $timeslot->slot_date)
                    ->where('start_time', '<', $timeslot->end_time)
                    ->where('end_time', '>', $timeslot->start_time);
            })->exists();

        if ($venueConflict) {
            $errors['venue_id'] = 'This venue already has another event during the selected time range.';
        }

        $blackoutConflict = VenueBlackout::where('venue_id', $venue->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($blackoutConflict) {
            $errors['venue_id'] = 'The venue is unavailable during this timeslot because of a blackout period.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function boundaries(Timeslot $timeslot): array
    {
        $date = $timeslot->slot_date instanceof Carbon
            ? $timeslot->slot_date->format('Y-m-d')
            : (string) $timeslot->slot_date;

        return [
            Carbon::parse($date.' '.$timeslot->start_time),
            Carbon::parse($date.' '.$timeslot->end_time),
        ];
    }
}
