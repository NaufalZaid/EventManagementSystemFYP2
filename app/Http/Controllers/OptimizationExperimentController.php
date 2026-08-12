<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\OptimizationExperiment;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Services\GeneticScheduleOptimizer;
use Illuminate\Http\Request;

class OptimizationExperimentController extends Controller
{
    public function __construct(private readonly GeneticScheduleOptimizer $optimizer) {}

    public function index()
    {
        $experiments = OptimizationExperiment::with('creator')->latest()->get();
        $eligibleCount = $this->eligibleEvents()->count();

        return view('experiments.index', compact('experiments', 'eligibleCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'repetitions' => ['required', 'integer', 'min:2', 'max:10'],
            'population_size' => ['required', 'integer', 'min:10', 'max:150'],
            'generations' => ['required', 'integer', 'min:5', 'max:300'],
            'mutation_rate' => ['required', 'numeric', 'min:0.01', 'max:0.5'],
            'base_seed' => ['required', 'integer', 'min:1', 'max:2147483000'],
        ]);
        $events = $this->eligibleEvents()->orderBy('id')->get();
        $venues = Venue::with('blackouts')->where('is_active', true)->orderBy('id')->get();
        $timeslots = Timeslot::whereDate('slot_date', '>=', today())->orderBy('slot_date')->orderBy('start_time')->get();
        abort_if($events->isEmpty() || $venues->isEmpty() || $timeslots->isEmpty(), 422, 'Approved events, active venues, and future timeslots are required.');

        $results = collect();
        for ($iteration = 0; $iteration < $validated['repetitions']; $iteration++) {
            $result = $this->optimizer->optimize($events, $venues, $timeslots, [
                'population_size' => $validated['population_size'],
                'generations' => $validated['generations'],
                'mutation_rate' => $validated['mutation_rate'],
                'seed' => $validated['base_seed'] + $iteration,
            ]);
            $results->push([
                'iteration' => $iteration + 1, 'seed' => $result['seed'],
                'fitness' => $result['fitness'], 'hard_conflicts' => $result['hard_conflicts'],
                'utilization_percent' => $result['utilization_percent'], 'execution_ms' => $result['execution_ms'],
            ]);
        }

        $experiment = OptimizationExperiment::create([
            'created_by' => $request->user()->id,
            ...$validated,
            'events_count' => $events->count(),
            'success_rate' => round($results->where('hard_conflicts', 0)->count() / $results->count() * 100, 2),
            'average_fitness' => round($results->avg('fitness'), 2),
            'best_fitness' => round($results->max('fitness'), 2),
            'average_utilization' => round($results->avg('utilization_percent'), 2),
            'average_execution_ms' => round($results->avg('execution_ms'), 2),
            'dataset' => [
                'event_ids' => $events->pluck('id')->all(),
                'venue_ids' => $venues->pluck('id')->all(),
                'timeslot_ids' => $timeslots->pluck('id')->all(),
            ],
            'results' => $results->all(),
        ]);

        return redirect()->route('experiments.show', $experiment)->with('success', 'Benchmark experiment completed and stored as evaluation evidence.');
    }

    public function show(OptimizationExperiment $experiment)
    {
        $experiment->load('creator');

        return view('experiments.show', compact('experiment'));
    }

    private function eligibleEvents()
    {
        return Event::where('status', EventStatus::Approved)
            ->whereDoesntHave('schedules')
            ->whereDoesntHave('venueRequests', fn ($query) => $query->where('status', 'pending'));
    }
}
