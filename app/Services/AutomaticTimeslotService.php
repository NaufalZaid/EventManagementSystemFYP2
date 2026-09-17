<?php

namespace App\Services;

use App\Models\Timeslot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AutomaticTimeslotService
{
    public const MAX_WINDOW_DAYS = 90;

    public function defaultStart(): Carbon
    {
        return today()->addDay();
    }

    public function defaultEnd(): Carbon
    {
        return $this->defaultStart()->copy()->addDays(29);
    }

    public function generate(Collection $events, Carbon $from, Carbon $to): Collection
    {
        $candidates = Timeslot::query()
            ->whereBetween('slot_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('slot_date')->orderBy('start_time')->get()
            ->filter(fn (Timeslot $timeslot) => $timeslot->slot_date->isWeekday())
            ->keyBy(fn (Timeslot $timeslot) => $this->key($timeslot->slot_date, $timeslot->start_time, $timeslot->end_time));

        $durations = $events->pluck('duration_minutes')->filter()->unique()->sort()->values();
        $closingHour = $events->contains('is_outside_working_hours', true)
            ? SchedulingTimePolicy::EXTENDED_CLOSING_HOUR
            : SchedulingTimePolicy::NORMAL_CLOSING_HOUR;

        foreach (CarbonPeriod::create($from, $to) as $date) {
            if (! $date->isWeekday()) {
                continue;
            }

            foreach ($durations as $durationMinutes) {
                $durationHours = (int) ceil($durationMinutes / 60);
                for ($startHour = SchedulingTimePolicy::OPENING_HOUR; $startHour + $durationHours <= $closingHour; $startHour++) {
                    $startTime = sprintf('%02d:00:00', $startHour);
                    $endTime = sprintf('%02d:00:00', $startHour + $durationHours);
                    $key = $this->key($date, $startTime, $endTime);

                    if (! $candidates->has($key)) {
                        $candidates->put($key, Timeslot::create([
                            'slot_date' => $date->toDateString(),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]));
                    }
                }
            }
        }

        return $candidates->values()->sortBy([
            ['slot_date', 'asc'],
            ['start_time', 'asc'],
            ['end_time', 'asc'],
        ])->values();
    }

    public function candidateCount(Collection $events, Carbon $from, Carbon $to): int
    {
        $weekdayCount = collect(CarbonPeriod::create($from, $to))->filter->isWeekday()->count();
        $closingHour = $events->contains('is_outside_working_hours', true)
            ? SchedulingTimePolicy::EXTENDED_CLOSING_HOUR
            : SchedulingTimePolicy::NORMAL_CLOSING_HOUR;

        return $events->pluck('duration_minutes')->filter()->unique()->sum(function (int $durationMinutes) use ($weekdayCount, $closingHour): int {
            $durationHours = (int) ceil($durationMinutes / 60);

            return max(0, $closingHour - SchedulingTimePolicy::OPENING_HOUR - $durationHours + 1) * $weekdayCount;
        });
    }

    private function key(Carbon $date, string $startTime, string $endTime): string
    {
        return $date->toDateString().'|'.substr($startTime, 0, 5).'|'.substr($endTime, 0, 5);
    }
}
