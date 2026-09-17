<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\OptimizationExperiment;
use App\Models\Venue;
use App\Services\AutomaticTimeslotService;
use App\Services\GeneticScheduleOptimizer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OptimizationExperimentController extends Controller
{
    public function __construct(
        private readonly GeneticScheduleOptimizer $optimizer,
        private readonly AutomaticTimeslotService $automaticTimeslots
    ) {}

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
            'scheduling_from' => ['nullable', 'date', 'after:today'],
            'scheduling_to' => ['nullable', 'date', 'after_or_equal:scheduling_from'],
        ]);
        $events = $this->eligibleEvents()->orderBy('id')->get();
        $venues = Venue::with('blackouts')->where('is_active', true)->orderBy('id')->get();
        $windowStart = Carbon::parse($validated['scheduling_from'] ?? $this->automaticTimeslots->defaultStart());
        $windowEnd = Carbon::parse($validated['scheduling_to'] ?? $this->automaticTimeslots->defaultEnd());
        abort_if($windowStart->diffInDays($windowEnd) >= AutomaticTimeslotService::MAX_WINDOW_DAYS, 422, 'The scheduling window cannot exceed 90 days.');
        $timeslots = $this->automaticTimeslots->generate($events, $windowStart, $windowEnd);
        abort_if($events->isEmpty() || $venues->isEmpty() || $timeslots->isEmpty(), 422, 'Approved events, active venues, and weekdays in the scheduling window are required.');

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
                'scheduling_from' => $windowStart->toDateString(),
                'scheduling_to' => $windowEnd->toDateString(),
            ],
            'results' => $results->all(),
        ]);

        return redirect()->route('experiments.show', $experiment)->with('success', 'GA performance test completed and saved.');
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
