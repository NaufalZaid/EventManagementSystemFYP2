<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\OptimizationExperiment;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Services\GeneticScheduleOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationAndReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_optimizer_reproduces_the_same_result_with_the_same_seed(): void
    {
        $organizer = User::factory()->organizer()->create();
        $venues = collect([
            Venue::create(['name' => 'Main Hall', 'capacity' => 120, 'is_active' => true])->load('blackouts'),
            Venue::create(['name' => 'Seminar Room', 'capacity' => 80, 'is_active' => true])->load('blackouts'),
        ]);
        $timeslots = collect([
            Timeslot::create(['slot_date' => today()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00']),
            Timeslot::create(['slot_date' => today()->addDays(6), 'start_time' => '14:00', 'end_time' => '16:00']),
        ]);
        $events = collect([
            $this->event($organizer, ['title' => 'First Candidate']),
            $this->event($organizer, ['title' => 'Second Candidate', 'capacity' => 70]),
        ]);
        $parameters = ['population_size' => 20, 'generations' => 10, 'mutation_rate' => 0.1, 'seed' => 12345];

        $first = app(GeneticScheduleOptimizer::class)->optimize($events, $venues, $timeslots, $parameters);
        $second = app(GeneticScheduleOptimizer::class)->optimize($events, $venues, $timeslots, $parameters);

        $this->assertSame(12345, $first['seed']);
        $this->assertSame($first['fitness'], $second['fitness']);
        $this->assertSame($first['hard_conflicts'], $second['hard_conflicts']);
        $this->assertSame($first['chromosome'], $second['chromosome']);
    }

    public function test_administrator_can_store_a_repeatable_ga_experiment(): void
    {
        $administrator = User::factory()->administrator()->create();
        $organizer = User::factory()->organizer()->create();
        Venue::create(['name' => 'Evaluation Hall', 'capacity' => 100, 'is_active' => true]);
        Timeslot::create(['slot_date' => today()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->event($organizer);

        $this->actingAs($administrator)->post(route('experiments.store'), [
            'name' => 'Baseline experiment',
            'repetitions' => 2,
            'population_size' => 10,
            'generations' => 5,
            'mutation_rate' => 0.1,
            'base_seed' => 700,
        ])->assertRedirect();

        $experiment = OptimizationExperiment::firstOrFail();
        $this->assertSame([700, 701], collect($experiment->results)->pluck('seed')->all());
        $this->assertSame(2, $experiment->repetitions);
        $this->assertSame(1, $experiment->events_count);
        $this->actingAs($administrator)->get(route('experiments.show', $experiment))
            ->assertOk()->assertSee('Baseline experiment')->assertSee('Base seed')->assertSee('700');
    }

    public function test_organizer_reports_are_date_filtered_scoped_and_exportable(): void
    {
        $organizer = User::factory()->organizer()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Report Hall', 'capacity' => 100, 'is_active' => true]);
        $slot = Timeslot::create(['slot_date' => today()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00']);
        $ownEvent = $this->event($organizer, ['title' => 'Owned Report Event', 'status' => EventStatus::Scheduled]);
        $otherEvent = $this->event($otherOrganizer, ['title' => 'Private Other Event', 'status' => EventStatus::Scheduled]);
        EventSchedule::create(['event_id' => $ownEvent->id, 'venue_id' => $venue->id, 'timeslot_id' => $slot->id, 'status' => 'manual']);
        EventSchedule::create(['event_id' => $otherEvent->id, 'venue_id' => $venue->id, 'timeslot_id' => $slot->id, 'status' => 'manual']);
        $dates = ['from' => today()->toDateString(), 'to' => today()->addDays(10)->toDateString()];

        $this->actingAs($organizer)->get(route('reports.index', $dates))
            ->assertOk()->assertSee('Owned Report Event')->assertDontSee('Private Other Event')->assertSee('Report Hall');

        $csv = $this->actingAs($organizer)->get(route('reports.events.csv', $dates));
        $csv->assertOk()->assertDownload();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Owned Report Event', $content);
        $this->assertStringNotContainsString('Private Other Event', $content);
    }

    public function test_authenticated_user_can_submit_and_update_one_consented_evaluation(): void
    {
        $student = User::factory()->create();
        $response = ['ease_of_use' => 4, 'usefulness' => 5, 'scheduling_confidence' => 4, 'satisfaction' => 5, 'comments' => 'Clear workflow.'];

        $this->actingAs($student)->put(route('evaluation.update'), $response)->assertSessionHasErrors('consent');
        $this->actingAs($student)->put(route('evaluation.update'), $response + ['consent' => '1'])->assertRedirect();
        $this->actingAs($student)->put(route('evaluation.update'), array_merge($response, ['satisfaction' => 3, 'consent' => '1']))->assertRedirect();

        $this->assertDatabaseCount('user_evaluations', 1);
        $this->assertDatabaseHas('user_evaluations', ['user_id' => $student->id, 'role' => 'student', 'satisfaction' => 3, 'consent' => true]);
    }

    public function test_evaluation_results_and_experiments_are_administrator_only(): void
    {
        $student = User::factory()->create();
        $organizer = User::factory()->organizer()->create();
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($student)->put(route('evaluation.update'), [
            'ease_of_use' => 4, 'usefulness' => 5, 'scheduling_confidence' => 4,
            'satisfaction' => 5, 'comments' => 'Anonymous evidence', 'consent' => '1',
        ]);

        $this->actingAs($student)->get(route('evaluation.results'))->assertForbidden();
        $this->actingAs($organizer)->get(route('experiments.index'))->assertForbidden();
        $this->actingAs($administrator)->get(route('evaluation.results'))
            ->assertOk()->assertSee('Anonymous evidence')->assertSee('4.5');
        $this->actingAs($administrator)->get(route('experiments.index'))->assertOk();
    }

    private function event(User $organizer, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Evaluation Candidate',
            'event_type' => 'workshop',
            'capacity' => 90,
            'duration_minutes' => 60,
            'status' => EventStatus::Approved,
        ], $attributes));
    }
}
