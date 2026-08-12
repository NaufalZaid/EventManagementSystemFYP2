<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GeneticScheduleOptimizer
{
    public function optimize(Collection $events, Collection $venues, Collection $timeslots, array $parameters): array
    {
        $startedAt = hrtime(true);
        $seed = (int) ($parameters['seed'] ?? random_int(1, PHP_INT_MAX));
        mt_srand($seed);
        $options = $this->buildOptions($events, $venues, $timeslots);
        $population = [];
        for ($i = 0; $i < $parameters['population_size']; $i++) {
            $population[] = $this->randomChromosome($events, $options);
        }

        $best = null;
        for ($generation = 0; $generation < $parameters['generations']; $generation++) {
            $evaluated = collect($population)->map(fn (array $chromosome) => $this->evaluate($chromosome, $events))
                ->sortByDesc('fitness')->values();
            if ($best === null || $evaluated->first()['fitness'] > $best['fitness']) {
                $best = $evaluated->first();
            }
            if ($best['hard_conflicts'] === 0 && $best['soft_penalty'] < 0.01) {
                break;
            }

            $next = [$evaluated[0]['chromosome'], $evaluated[1]['chromosome'] ?? $evaluated[0]['chromosome']];
            while (count($next) < $parameters['population_size']) {
                $parentA = $this->tournament($evaluated);
                $parentB = $this->tournament($evaluated);
                $child = $this->crossover($parentA, $parentB);
                $next[] = $this->mutate($child, $options, $parameters['mutation_rate']);
            }
            $population = $next;
        }

        $best ??= $this->evaluate([], $events);
        $best['execution_ms'] = max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
        $best['available_options'] = collect($options)->map->count()->sum();
        $best['seed'] = $seed;

        return $best;
    }

    private function buildOptions(Collection $events, Collection $venues, Collection $timeslots): array
    {
        $existing = EventSchedule::with('timeslot')->get();
        $options = [];

        foreach ($events as $event) {
            $options[$event->id] = collect();
            foreach ($venues as $venue) {
                foreach ($timeslots as $timeslot) {
                    $startsAt = Carbon::parse($timeslot->slot_date->format('Y-m-d').' '.$timeslot->start_time);
                    $endsAt = Carbon::parse($timeslot->slot_date->format('Y-m-d').' '.$timeslot->end_time);
                    if (! $venue->is_active || $event->capacity > $venue->capacity
                        || $endsAt->lessThanOrEqualTo($startsAt)
                        || $startsAt->diffInMinutes($endsAt) < $event->duration_minutes) {
                        continue;
                    }
                    if ($venue->blackouts->contains(fn ($blackout) => $blackout->starts_at->lt($endsAt) && $blackout->ends_at->gt($startsAt))) {
                        continue;
                    }
                    if ($existing->where('venue_id', $venue->id)->contains(function ($schedule) use ($timeslot): bool {
                        return $schedule->timeslot->slot_date->isSameDay($timeslot->slot_date)
                            && $timeslot->start_time < $schedule->timeslot->end_time
                            && $timeslot->end_time > $schedule->timeslot->start_time;
                    })) {
                        continue;
                    }

                    [$penalty, $details] = $this->softPenalty($event, $venue, $timeslot);
                    $options[$event->id]->push([
                        'event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslot->id,
                        'date' => $timeslot->slot_date->format('Y-m-d'), 'start' => $timeslot->start_time,
                        'end' => $timeslot->end_time, 'venue_capacity' => $venue->capacity,
                        'soft_penalty' => $penalty, 'details' => $details,
                    ]);
                }
            }
        }

        return $options;
    }

    private function softPenalty(Event $event, Venue $venue, Timeslot $timeslot): array
    {
        $unusedRatio = $venue->capacity > 0 ? ($venue->capacity - $event->capacity) / $venue->capacity : 1;
        $penalty = max(0, $unusedRatio * 20);
        $details = ['unused_capacity' => $venue->capacity - $event->capacity];

        if ($event->preferred_venue_id) {
            $details['preferred_venue_met'] = $event->preferred_venue_id === $venue->id;
            $penalty += $details['preferred_venue_met'] ? 0 : 20;
        }
        if ($event->preferred_date) {
            $details['preferred_date_met'] = $event->preferred_date->isSameDay($timeslot->slot_date);
            $penalty += $details['preferred_date_met'] ? 0 : 15;
        }
        if ($event->preferred_start_time) {
            $details['preferred_time_met'] = substr($event->preferred_start_time, 0, 5) === substr($timeslot->start_time, 0, 5);
            $penalty += $details['preferred_time_met'] ? 0 : 10;
        }

        return [round($penalty, 2), $details];
    }

    private function randomChromosome(Collection $events, array $options): array
    {
        $chromosome = [];
        foreach ($events as $event) {
            $eventOptions = $options[$event->id];
            $chromosome[$event->id] = $eventOptions->isEmpty() ? null : $eventOptions->values()[mt_rand(0, $eventOptions->count() - 1)];
        }

        return $chromosome;
    }

    private function evaluate(array $chromosome, Collection $events): array
    {
        $hardConflicts = 0;
        $softPenalty = 0.0;
        $assigned = array_values(array_filter($chromosome));
        foreach ($events as $event) {
            $option = $chromosome[$event->id] ?? null;
            if (! $option) {
                $hardConflicts++;

                continue;
            }
            $softPenalty += $option['soft_penalty'];
        }
        for ($i = 0; $i < count($assigned); $i++) {
            for ($j = $i + 1; $j < count($assigned); $j++) {
                if ($assigned[$i]['venue_id'] === $assigned[$j]['venue_id']
                    && $assigned[$i]['date'] === $assigned[$j]['date']
                    && $assigned[$i]['start'] < $assigned[$j]['end']
                    && $assigned[$i]['end'] > $assigned[$j]['start']) {
                    $hardConflicts++;
                }
            }
        }

        $usedCapacity = 0;
        $allocatedCapacity = 0;
        foreach ($events as $event) {
            if ($option = $chromosome[$event->id] ?? null) {
                $usedCapacity += $event->capacity;
                $allocatedCapacity += $option['venue_capacity'];
            }
        }
        $utilization = $allocatedCapacity ? $usedCapacity / $allocatedCapacity * 100 : 0;

        return [
            'chromosome' => $chromosome,
            'fitness' => round(max(0, 10000 - ($hardConflicts * 1000) - $softPenalty), 2),
            'hard_conflicts' => $hardConflicts,
            'soft_penalty' => round($softPenalty, 2),
            'utilization_percent' => round($utilization, 2),
        ];
    }

    private function tournament(Collection $evaluated): array
    {
        $competitors = collect();
        for ($i = 0; $i < min(3, $evaluated->count()); $i++) {
            $competitors->push($evaluated[mt_rand(0, $evaluated->count() - 1)]);
        }

        return $competitors->sortByDesc('fitness')->first()['chromosome'];
    }

    private function crossover(array $parentA, array $parentB): array
    {
        $child = [];
        foreach ($parentA as $eventId => $gene) {
            $child[$eventId] = mt_rand(0, 1) ? $gene : ($parentB[$eventId] ?? $gene);
        }

        return $child;
    }

    private function mutate(array $chromosome, array $options, float $mutationRate): array
    {
        foreach ($chromosome as $eventId => $gene) {
            if (mt_rand(0, 10000) / 10000 <= $mutationRate && $options[$eventId]->isNotEmpty()) {
                $eventOptions = $options[$eventId]->values();
                $chromosome[$eventId] = $eventOptions[mt_rand(0, $eventOptions->count() - 1)];
            }
        }

        return $chromosome;
    }
}
