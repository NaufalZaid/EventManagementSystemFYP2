<?php

namespace App\Services;

use App\Models\EventSchedule;
use App\Models\Venue;
use App\Notifications\EventRescheduledNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BlackoutReschedulingService
{
    private const SEARCH_DAYS = 14;

    public function __construct(
        private readonly AutomaticTimeslotService $automaticTimeslots,
        private readonly GeneticScheduleOptimizer $optimizer,
    ) {}

    public function reschedule(Collection $blackoutVenues, Carbon $startsAt, Carbon $endsAt, string $reason): int
    {
        $schedules = EventSchedule::with(['event.organizer', 'venue', 'timeslot'])
            ->whereIn('venue_id', $blackoutVenues->pluck('id'))
            ->whereHas('timeslot', fn ($query) => $query
                ->whereDate('slot_date', '>=', $startsAt->toDateString())
                ->whereDate('slot_date', '<=', $endsAt->toDateString()))
            ->lockForUpdate()
            ->get()
            ->filter(function (EventSchedule $schedule) use ($startsAt, $endsAt): bool {
                [$eventStarts, $eventEnds] = $this->boundaries($schedule);

                return $eventStarts->isFuture() && $eventStarts->lt($endsAt) && $eventEnds->gt($startsAt);
            })
            ->values();

        if ($schedules->isEmpty()) {
            return 0;
        }

        $originals = $schedules->mapWithKeys(fn (EventSchedule $schedule) => [
            $schedule->event_id => $this->scheduleData($schedule),
        ]);
        $events = $schedules->pluck('event')->unique('id')->values();

        // Preserve the organizer's original date and time, but allow the GA to
        // freely replace a venue that has just become unavailable.
        $schedules->each(function (EventSchedule $schedule): void {
            $event = $schedule->event;
            $event->preferred_date = $schedule->timeslot->slot_date;
            $event->preferred_start_time = $schedule->timeslot->start_time;
            $event->preferred_venue_id = null;
        });

        $schedules->each->delete();

        $windowStart = $schedules->min(fn (EventSchedule $schedule) => $schedule->timeslot->slot_date->copy());
        $windowEnd = $schedules->max(fn (EventSchedule $schedule) => $schedule->timeslot->slot_date->copy())
            ->addDays(self::SEARCH_DAYS);
        $timeslots = $this->automaticTimeslots->generate($events, $windowStart, $windowEnd);
        $venues = Venue::with('blackouts')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
        $seed = max(1, (int) sprintf('%u', crc32($events->pluck('id')->join('-').$startsAt->toIso8601String())));
        $result = $this->optimizer->optimize($events, $venues, $timeslots, [
            'population_size' => min(150, max(60, $events->count() * 30)),
            'generations' => 250,
            'mutation_rate' => 0.10,
            'seed' => $seed,
        ]);

        if ($result['hard_conflicts'] > 0) {
            throw ValidationException::withMessages([
                'blackout_date' => 'The blackout cannot be saved because no conflict-free replacement schedule is available within the next '.self::SEARCH_DAYS.' days.',
            ]);
        }

        foreach ($events as $event) {
            $assignment = $result['chromosome'][$event->id] ?? null;
            if (! $assignment) {
                throw ValidationException::withMessages([
                    'blackout_date' => 'The blackout cannot be saved because an affected event could not be rescheduled.',
                ]);
            }

            $replacement = EventSchedule::create([
                'event_id' => $event->id,
                'venue_id' => $assignment['venue_id'],
                'timeslot_id' => $assignment['timeslot_id'],
                'status' => 'generated',
            ])->load(['venue', 'timeslot']);

            $event->organizer?->notify(new EventRescheduledNotification(
                $event,
                $originals[$event->id],
                $this->scheduleData($replacement),
                $reason,
            ));
        }

        return $events->count();
    }

    private function boundaries(EventSchedule $schedule): array
    {
        $date = $schedule->timeslot->slot_date->toDateString();

        return [
            Carbon::parse($date.' '.$schedule->timeslot->start_time),
            Carbon::parse($date.' '.$schedule->timeslot->end_time),
        ];
    }

    private function scheduleData(EventSchedule $schedule): array
    {
        return [
            'date' => $schedule->timeslot->slot_date->format('d M Y'),
            'start' => Carbon::parse($schedule->timeslot->start_time)->format('g:i A'),
            'end' => Carbon::parse($schedule->timeslot->end_time)->format('g:i A'),
            'venue' => $schedule->venue->name,
        ];
    }
}
