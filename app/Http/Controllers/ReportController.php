<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\OptimizationExperiment;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->dates($request);
        $events = $this->events($request, $from, $to);
        $venues = $this->venues($events);

        return view('reports.index', compact('events', 'venues', 'from', 'to'));
    }

    public function eventsCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dates($request);
        $events = $this->events($request, $from, $to);

        return response()->streamDownload(function () use ($events): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Event', 'Organizer', 'Date', 'Venue', 'Capacity', 'Registrations', 'Attendance', 'Attendance Rate %', 'Schedule Source']);
            foreach ($events as $event) {
                $schedule = $event->schedules->first();
                fputcsv($file, [
                    $event->title, $event->organizer?->name, $schedule?->timeslot->slot_date->format('Y-m-d'),
                    $schedule?->venue->name, $event->capacity, $event->registered_count, $event->attended_count,
                    $event->registered_count ? round($event->attended_count / $event->registered_count * 100, 2) : 0,
                    $schedule?->status,
                ]);
            }
            fclose($file);
        }, "event-performance-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    public function venuesCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dates($request);
        $events = $this->events($request, $from, $to);
        $venues = $this->venues($events);

        return response()->streamDownload(function () use ($venues): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Venue', 'Scheduled Events', 'Required Seats', 'Allocated Seats', 'Capacity Utilization %', 'Occupied Minutes']);
            foreach ($venues as $venue) {
                fputcsv($file, [$venue['name'], $venue['event_count'], $venue['required_seats'], $venue['allocated_seats'], $venue['capacity_utilization'], $venue['occupied_minutes']]);
            }
            fclose($file);
        }, "venue-utilization-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    public function experimentsCsv(): StreamedResponse
    {
        $experiments = OptimizationExperiment::with('creator')->latest()->get();

        return response()->streamDownload(function () use ($experiments): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Experiment', 'Created By', 'Repetitions', 'Population', 'Generations', 'Mutation Rate', 'Base Seed', 'Events', 'Success Rate %', 'Average Fitness', 'Best Fitness', 'Average Utilization %', 'Average Runtime ms']);
            foreach ($experiments as $experiment) {
                fputcsv($file, [$experiment->name, $experiment->creator->name, $experiment->repetitions, $experiment->population_size, $experiment->generations, $experiment->mutation_rate, $experiment->base_seed, $experiment->events_count, $experiment->success_rate, $experiment->average_fitness, $experiment->best_fitness, $experiment->average_utilization, $experiment->average_execution_ms]);
            }
            fclose($file);
        }, 'ga-experiment-evidence.csv', ['Content-Type' => 'text/csv']);
    }

    private function dates(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [$validated['from'] ?? now()->startOfYear()->toDateString(), $validated['to'] ?? now()->endOfYear()->toDateString()];
    }

    private function events(Request $request, string $from, string $to)
    {
        return Event::with([
            'organizer',
            'schedules' => fn ($query) => $query->whereHas('timeslot', fn ($query) => $query->whereBetween('slot_date', [$from, $to]))->with(['venue', 'timeslot']),
        ])->withCount([
            'registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered),
            'attendanceRecords as attended_count',
        ])->whereHas('schedules.timeslot', fn ($query) => $query->whereBetween('slot_date', [$from, $to]))
            ->when($request->user()->hasRole('organizer'), fn ($query) => $query->where('organizer_id', $request->user()->id))
            ->get()->sortBy(fn ($event) => $event->schedules->first()?->timeslot->slot_date);
    }

    private function venues($events)
    {
        return $events->groupBy(fn ($event) => $event->schedules->first()?->venue_id)->map(function ($venueEvents, $venueId): array {
            $venue = Venue::find($venueId);
            $required = $venueEvents->sum('capacity');
            $allocated = $venueEvents->count() * ($venue?->capacity ?? 0);
            $minutes = $venueEvents->sum(function ($event): int {
                $slot = $event->schedules->first()->timeslot;

                return (int) round(Carbon::parse($slot->start_time)->diffInMinutes(Carbon::parse($slot->end_time)));
            });

            return [
                'name' => $venue?->name ?? 'Deleted venue', 'event_count' => $venueEvents->count(),
                'required_seats' => $required, 'allocated_seats' => $allocated,
                'capacity_utilization' => $allocated ? round($required / $allocated * 100, 2) : 0,
                'occupied_minutes' => $minutes,
            ];
        })->values();
    }
}
