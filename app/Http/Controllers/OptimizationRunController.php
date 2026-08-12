<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\OptimizationRun;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Services\GeneticScheduleOptimizer;
use App\Services\SchedulingConstraintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptimizationRunController extends Controller
{
    public function __construct(
        private readonly GeneticScheduleOptimizer $optimizer,
        private readonly SchedulingConstraintService $constraints,
    ) {}

    public function index()
    {
        $runs = OptimizationRun::with('creator')->latest()->get();
        $eligibleCount = $this->eligibleEvents()->count();
        $venueCount = Venue::where('is_active', true)->count();
        $timeslotCount = Timeslot::whereDate('slot_date', '>=', today())->count();

        return view('optimizer.index', compact('runs', 'eligibleCount', 'venueCount', 'timeslotCount'));
    }

    public function store(Request $request)
    {
        $parameters = $request->validate([
            'population_size' => ['required', 'integer', 'min:10', 'max:300'],
            'generations' => ['required', 'integer', 'min:5', 'max:1000'],
            'mutation_rate' => ['required', 'numeric', 'min:0.01', 'max:0.5'],
            'seed' => ['nullable', 'integer', 'min:1'],
        ]);
        $events = $this->eligibleEvents()->orderBy('id')->get();
        $venues = Venue::with('blackouts')->where('is_active', true)->orderBy('id')->get();
        $timeslots = Timeslot::whereDate('slot_date', '>=', today())->orderBy('slot_date')->orderBy('start_time')->get();
        abort_if($events->isEmpty(), 422, 'There are no approved unscheduled events to optimize.');
        abort_if($venues->isEmpty() || $timeslots->isEmpty(), 422, 'Active venues and future timeslots are required.');

        $result = $this->optimizer->optimize($events, $venues, $timeslots, $parameters);
        $run = DB::transaction(function () use ($request, $parameters, $events, $result): OptimizationRun {
            $run = OptimizationRun::create([
                'created_by' => $request->user()->id,
                'status' => 'completed',
                'population_size' => $parameters['population_size'],
                'generations' => $parameters['generations'],
                'mutation_rate' => $parameters['mutation_rate'],
                'events_count' => $events->count(),
                'best_fitness' => $result['fitness'],
                'hard_conflicts' => $result['hard_conflicts'],
                'utilization_percent' => $result['utilization_percent'],
                'execution_ms' => $result['execution_ms'],
                'metrics' => ['soft_penalty' => $result['soft_penalty'], 'available_options' => $result['available_options'], 'seed' => $result['seed']],
            ]);
            foreach ($events as $event) {
                $gene = $result['chromosome'][$event->id] ?? null;
                $run->assignments()->create([
                    'event_id' => $event->id,
                    'venue_id' => $gene['venue_id'] ?? null,
                    'timeslot_id' => $gene['timeslot_id'] ?? null,
                    'soft_penalty' => $gene['soft_penalty'] ?? 0,
                    'details' => $gene['details'] ?? ['unassigned' => true],
                ]);
            }

            return $run;
        });

        return redirect()->route('optimizer.show', $run)->with('success', 'Genetic Algorithm run completed. Review the result before applying it.');
    }

    public function show(OptimizationRun $run)
    {
        $run->load(['creator', 'assignments.event.organizer', 'assignments.venue', 'assignments.timeslot']);

        return view('optimizer.show', compact('run'));
    }

    public function apply(OptimizationRun $run)
    {
        abort_if($run->hard_conflicts > 0, 422, 'Only a conflict-free optimization result can be applied.');
        abort_if($run->applied_at, 422, 'This optimization run has already been applied.');

        DB::transaction(function () use ($run): void {
            $run = OptimizationRun::with(['assignments.event', 'assignments.venue', 'assignments.timeslot'])
                ->lockForUpdate()->findOrFail($run->id);
            abort_if($run->applied_at, 422, 'This optimization run has already been applied.');
            foreach ($run->assignments as $assignment) {
                abort_unless($assignment->venue && $assignment->timeslot, 422, 'The result contains an unassigned event.');
                abort_unless($assignment->event->status === EventStatus::Approved, 422, 'An event is no longer eligible for scheduling.');
                abort_if($assignment->event->venueRequests()->where('status', 'pending')->exists(), 422, 'An event now has a pending venue request.');
                $this->constraints->validate($assignment->event, $assignment->venue, $assignment->timeslot);
                EventSchedule::create([
                    'event_id' => $assignment->event_id,
                    'venue_id' => $assignment->venue_id,
                    'timeslot_id' => $assignment->timeslot_id,
                    'status' => 'generated',
                ]);
                $assignment->event->update(['status' => EventStatus::Scheduled]);
            }
            $run->update(['status' => 'applied', 'applied_at' => now()]);
        });

        return redirect()->route('schedules.index')->with('success', 'Conflict-free GA schedule applied successfully.');
    }

    public function comparison()
    {
        $manual = $this->scheduleMetrics(EventSchedule::where('status', 'manual')->with(['event', 'venue'])->get());
        $generated = $this->scheduleMetrics(EventSchedule::where('status', 'generated')->with(['event', 'venue'])->get());
        $runs = OptimizationRun::latest()->take(10)->get();

        return view('optimizer.comparison', compact('manual', 'generated', 'runs'));
    }

    private function scheduleMetrics($schedules): array
    {
        $allocated = $schedules->sum(fn ($schedule) => $schedule->venue->capacity);
        $required = $schedules->sum(fn ($schedule) => $schedule->event->capacity);

        return [
            'count' => $schedules->count(),
            'utilization' => $allocated ? round($required / $allocated * 100, 2) : 0,
            'unused_seats' => max(0, $allocated - $required),
            'conflicts' => 0,
        ];
    }

    private function eligibleEvents()
    {
        return Event::where('status', EventStatus::Approved)
            ->whereDoesntHave('schedules')
            ->whereDoesntHave('venueRequests', fn ($query) => $query->where('status', 'pending'));
    }
}
