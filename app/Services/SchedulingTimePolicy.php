<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SchedulingTimePolicy
{
    public const OPENING_HOUR = 8;

    public const NORMAL_CLOSING_HOUR = 18;

    public const EXTENDED_CLOSING_HOUR = 23;

    public function validate(string $date, string $startTime, string $endTime, bool $outsideWorkingHours): void
    {
        $startsAt = Carbon::parse($date.' '.$startTime);
        $endsAt = Carbon::parse($date.' '.$endTime);
        $closingHour = $outsideWorkingHours ? self::EXTENDED_CLOSING_HOUR : self::NORMAL_CLOSING_HOUR;
        $errors = [];

        if ($startsAt->isWeekend()) {
            $errors['slot_date'] = 'Weekends are blackout periods. Select a weekday.';
        }
        if ($startsAt->minute !== 0 || $startsAt->second !== 0) {
            $errors['start_time'] = 'The start time must be on the hour (for example, 09:00).';
        }
        if ($endsAt->minute !== 0 || $endsAt->second !== 0) {
            $errors['end_time'] = 'The end time must be on the hour (for example, 12:00).';
        } elseif ($endsAt->lessThanOrEqualTo($startsAt)) {
            $errors['end_time'] = 'The end time must be after the start time.';
        }
        if ($startsAt->hour < self::OPENING_HOUR || $startsAt->hour >= $closingHour) {
            $errors['start_time'] = 'The start time must be between 08:00 and '.sprintf('%02d:00', $closingHour - 1).'.';
        }
        if ($endsAt->hour > $closingHour || $endsAt->hour === 0) {
            $errors['end_time'] = 'The end time must be no later than '.sprintf('%02d:00', $closingHour).'.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
