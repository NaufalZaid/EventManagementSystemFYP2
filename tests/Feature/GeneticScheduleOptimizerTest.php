<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\OptimizationRun;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Services\GeneticScheduleOptimizer;
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
        $timeslot = Timeslot::create(['slot_date' => today()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00']);
        $event = $this->approvedEvent($organizer, ['preferred_venue_id' => $venue->id, 'preferred_date' => $timeslot->slot_date, 'preferred_start_time' => '09:00']);

        $this->actingAs($administrator)->post(route('optimizer.store'), [
            'population_size' => 20, 'generations' => 20, 'mutation_rate' => 0.08,
        ])->assertRedirect();

        $run = OptimizationRun::with('assignments')->firstOrFail();
        $this->assertSame(0, $run->hard_conflicts);
        $this->assertSame($venue->id, $run->assignments->first()->venue_id);
        $this->assertSame($timeslot->id, $run->assignments->first()->timeslot_id);
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
        Timeslot::create(['slot_date' => today()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00']);
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
