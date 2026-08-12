<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\EventRegistration;
use App\Models\PersonalCommitment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m-d', ($validated['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $registrations = $request->user()->eventRegistrations()
            ->where('status', RegistrationStatus::Registered)
            ->whereHas('event.schedules.timeslot', fn ($query) => $query->whereBetween('slot_date', [$month->toDateString(), $monthEnd->toDateString()]))
            ->with(['event.schedules.venue', 'event.schedules.timeslot'])->get();
        $commitments = $request->user()->personalCommitments()
            ->where('starts_at', '<=', $monthEnd)
            ->where('ends_at', '>=', $month)
            ->get();

        $entries = $registrations->map(function (EventRegistration $registration): array {
            $schedule = $registration->event->schedules->first();
            $timeslot = $schedule->timeslot;

            return [
                'key' => 'event-'.$registration->id,
                'kind' => 'event',
                'title' => $registration->event->title,
                'subtitle' => $schedule->venue->name,
                'starts_at' => $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->start_time),
                'ends_at' => $timeslot->slot_date->copy()->setTimeFromTimeString($timeslot->end_time),
                'url' => route('discover.show', $registration->event),
            ];
        })->concat($commitments->map(fn (PersonalCommitment $commitment): array => [
            'key' => 'commitment-'.$commitment->id,
            'kind' => 'commitment',
            'title' => $commitment->title,
            'subtitle' => str($commitment->commitment_type)->headline().($commitment->location ? ' · '.$commitment->location : ''),
            'starts_at' => $commitment->starts_at,
            'ends_at' => $commitment->ends_at,
            'url' => route('commitments.edit', $commitment),
        ]))->sortBy('starts_at')->values();

        $entries = $entries->map(function (array $entry) use ($entries): array {
            $entry['has_conflict'] = $entries->contains(fn (array $other) => $entry['key'] !== $other['key']
                && $entry['starts_at']->lessThan($other['ends_at'])
                && $entry['ends_at']->greaterThan($other['starts_at']));

            return $entry;
        });
        $entriesByDate = $entries->groupBy(fn (array $entry) => $entry['starts_at']->toDateString());

        return view('calendar.index', [
            'month' => $month,
            'entries' => $entries,
            'entriesByDate' => $entriesByDate,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
