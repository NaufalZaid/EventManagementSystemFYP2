<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\OptimizationRun;
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

    public function test_administrator_can_generate_review_and_apply_a_conflict_free_result(): void
    {
        $administrator = User::factory()->administrator()->create();
        $organizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Exact Fit Hall', 'capacity' => 100, 'is_active' => true]);
        $preferredDate = today()->addWeekday();
        $event = $this->approvedEvent($organizer, ['preferred_venue_id' => $venue->id, 'preferred_date' => $preferredDate, 'preferred_start_time' => '09:00']);

        $this->assertDatabaseCount('timeslots', 0);

        $this->actingAs($administrator)->post(route('optimizer.store'), [
            'population_size' => 20, 'generations' => 20, 'mutation_rate' => 0.08,
        ])->assertRedirect();

        $run = OptimizationRun::with('assignments')->firstOrFail();
        $this->assertSame(0, $run->hard_conflicts);
        $this->assertSame($venue->id, $run->assignments->first()->venue_id);
        $generatedTimeslot = $run->assignments->first()->timeslot;
        $this->assertTrue($generatedTimeslot->slot_date->isSameDay($preferredDate));
        $this->assertTrue($generatedTimeslot->slot_date->isWeekday());
        $this->assertStringEndsWith(':00:00', $generatedTimeslot->start_time);
        $this->assertTrue(Timeslot::whereDate('slot_date', $preferredDate)->where('start_time', '09:00:00')->exists());
        $this->actingAs($administrator)->get(route('optimizer.show', $run))->assertOk()->assertSee('Venue met')->assertSee('Apply generated schedule');

        $this->actingAs($administrator)->post(route('optimizer.apply', $run))->assertRedirect(route('schedules.index'));
        $this->assertDatabaseHas('event_schedules', ['event_id' => $event->id, 'status' => 'generated']);
        $this->assertSame(EventStatus::Scheduled, $event->fresh()->status);
        $this->assertNotNull($run->fresh()->applied_at);
    }

    public function test_infeasible_result_records_a_hard_conflict_and_cannot_be_applied(): void
    {
        $administrator = User::factory()->administrator()->create();
        $organizer = User::factory()->organizer()->create();
        Venue::create(['name' => 'Tiny Room', 'capacity' => 10, 'is_active' => true]);
        $this->approvedEvent($organizer);

        $this->actingAs($administrator)->post(route('optimizer.store'), [
            'population_size' => 10, 'generations' => 5, 'mutation_rate' => 0.1,
        ])->assertRedirect();
        $run = OptimizationRun::firstOrFail();

        $this->assertSame(1, $run->hard_conflicts);
        $this->actingAs($administrator)->post(route('optimizer.apply', $run))->assertStatus(422);
        $this->assertDatabaseCount('event_schedules', 0);
    }

    public function test_fitness_detects_competing_events_for_the_same_venue_and_time(): void
    {
        $organizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Only Hall', 'capacity' => 100, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => today()->addDays(7), 'start_time' => '09:00', 'end_time' => '11:00']);
        $events = collect([$this->approvedEvent($organizer), $this->approvedEvent($organizer, ['title' => 'Second Event'])]);

        $result = app(GeneticScheduleOptimizer::class)->optimize(
            $events, collect([$venue->load('blackouts')]), collect([$timeslot]),
            ['population_size' => 10, 'generations' => 5, 'mutation_rate' => 0.1]
        );

        $this->assertSame(1, $result['hard_conflicts']);
        $this->assertLessThan(10000, $result['fitness']);
    }

    public function test_optimizer_is_restricted_to_administrators(): void
    {
        $this->actingAs(User::factory()->organizer()->create())->get(route('optimizer.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('optimizer.index'))->assertForbidden();
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
