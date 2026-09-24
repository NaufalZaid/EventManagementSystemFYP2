<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Services\AutomaticTimeslotService;
use App\Services\GeneticScheduleOptimizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneticScheduleOptimizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fitness_detects_competing_events_for_the_same_venue_and_time(): void
    {
        $organizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Only Hall', 'capacity' => 100, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => today()->next(Carbon::MONDAY), 'start_time' => '09:00', 'end_time' => '11:00']);
        $events = collect([$this->approvedEvent($organizer), $this->approvedEvent($organizer, ['title' => 'Second Event'])]);

        $result = app(GeneticScheduleOptimizer::class)->optimize(
            $events, collect([$venue->load('blackouts')]), collect([$timeslot]),
            ['population_size' => 10, 'generations' => 5, 'mutation_rate' => 0.1]
        );

        $this->assertSame(1, $result['hard_conflicts']);
        $this->assertLessThan(10000, $result['fitness']);
    }

    public function test_automatic_candidates_cover_weekday_hours_and_respect_extended_events(): void
    {
        $organizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Open Hall', 'capacity' => 100, 'is_active' => true])->load('blackouts');
        $normal = $this->approvedEvent($organizer);
        $extended = $this->approvedEvent($organizer, ['title' => 'Evening Event', 'is_outside_working_hours' => true]);
        $monday = today()->next(Carbon::MONDAY);
        $candidates = app(AutomaticTimeslotService::class)->generate(collect([$normal, $extended]), $monday, $monday);

        $this->assertTrue($candidates->every(fn (Timeslot $timeslot) => $timeslot->slot_date->isWeekday()));
        $this->assertTrue($candidates->contains(fn (Timeslot $timeslot) => $timeslot->start_time === '08:00:00'));
        $evening = $candidates->first(fn (Timeslot $timeslot) => $timeslot->start_time === '22:00:00' && $timeslot->end_time === '23:00:00');
        $this->assertNotNull($evening);
        $parameters = ['population_size' => 10, 'generations' => 5, 'mutation_rate' => 0.1, 'seed' => 99];

        $normalResult = app(GeneticScheduleOptimizer::class)->optimize(collect([$normal]), collect([$venue]), collect([$evening]), $parameters);
        $extendedResult = app(GeneticScheduleOptimizer::class)->optimize(collect([$extended]), collect([$venue]), collect([$evening]), $parameters);

        $this->assertSame(1, $normalResult['hard_conflicts']);
        $this->assertSame(0, $extendedResult['hard_conflicts']);
    }

    private function approvedEvent(User $organizer, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Optimization Candidate',
            'event_type' => 'workshop',
            'capacity' => 100,
            'duration_minutes' => 60,
            'status' => EventStatus::Approved,
        ], $attributes));
    }
}
