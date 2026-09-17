<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\VenueRequestStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\Venue;
use App\Models\VenueRequest;
use App\Services\SchedulingConstraintService;
use App\Services\SchedulingTimePolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VenueRequestController extends Controller
{
    public function __construct(
        private readonly SchedulingConstraintService $constraints,
        private readonly SchedulingTimePolicy $timePolicy
    ) {}

    public function index(Request $request)
    {
        $requests = VenueRequest::with(['event.organizer', 'venue', 'timeslot', 'requester', 'reviewer'])
            ->when($request->user()->hasRole('organizer'), fn ($query) => $query->where('requested_by', $request->user()->id))
            ->latest()
            ->get();

        return view('venue-requests.index', compact('requests'));
    }

    public function create(Request $request)
    {
        $events = Event::where('organizer_id', $request->user()->id)
            ->where('status', EventStatus::Approved)
            ->whereDoesntHave('venueRequests', fn ($query) => $query->whereIn('status', [VenueRequestStatus::Pending, VenueRequestStatus::Approved]))
            ->orderBy('title')->get();
        $selectedEvent = $events->firstWhere('id', (int) $request->input('event_id'));
        $slotDate = $request->input('slot_date', $selectedEvent?->preferred_date?->format('Y-m-d'));
        $startTime = $request->input('start_time', $selectedEvent?->preferred_start_time ? substr($selectedEvent->preferred_start_time, 0, 5) : null);
        $endTime = $request->input('end_time');
        $venues = collect();
        $availabilityChecked = $selectedEvent && $slotDate && $startTime && $endTime;

        if ($availabilityChecked) {
            $this->timePolicy->validate($slotDate, $startTime, $endTime, $selectedEvent->is_outside_working_hours);
            if (Carbon::parse($slotDate.' '.$startTime)->diffInMinutes(Carbon::parse($slotDate.' '.$endTime)) < $selectedEvent->duration_minutes) {
                throw ValidationException::withMessages([
                    'end_time' => 'The selected hours are shorter than the event duration.',
                ]);
            }
            $candidate = new Timeslot(['slot_date' => $slotDate, 'start_time' => $startTime, 'end_time' => $endTime]);
            $venues = $this->constraints->availableVenues($selectedEvent, $candidate);
        }

        return view('venue-requests.create', compact(
            'events', 'selectedEvent', 'slotDate', 'startTime', 'endTime', 'venues', 'availabilityChecked'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'venue_id' => ['required', 'exists:venues,id'],
            'timeslot_id' => ['nullable', 'exists:timeslots,id'],
            'slot_date' => ['required_without:timeslot_id', 'date'],
            'start_time' => ['required_without:timeslot_id', 'date_format:H:i'],
            'end_time' => ['required_without:timeslot_id', 'date_format:H:i'],
            'organizer_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $event = Event::findOrFail($validated['event_id']);
        abort_unless($event->organizer_id === $request->user()->id, 403);
        abort_unless($event->status === EventStatus::Approved, 422, 'Only approved events can request a venue.');
        abort_if($event->venueRequests()->whereIn('status', [VenueRequestStatus::Pending, VenueRequestStatus::Approved])->exists(), 422, 'This event already has an active venue request.');

        $venue = Venue::findOrFail($validated['venue_id']);

        DB::transaction(function () use ($validated, $event, $venue, $request): void {
            if (! empty($validated['timeslot_id'])) {
                $timeslot = Timeslot::findOrFail($validated['timeslot_id']);
            } else {
                $this->timePolicy->validate(
                    $validated['slot_date'],
                    $validated['start_time'],
                    $validated['end_time'],
                    $event->is_outside_working_hours
                );
                $timeslot = Timeslot::query()
                    ->whereDate('slot_date', $validated['slot_date'])
                    ->where('start_time', $validated['start_time'].':00')
                    ->where('end_time', $validated['end_time'].':00')
                    ->first() ?? Timeslot::create([
                        'slot_date' => $validated['slot_date'],
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                    ]);
            }

            $this->constraints->validate($event, $venue, $timeslot);
            VenueRequest::create([
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'timeslot_id' => $timeslot->id,
                'organizer_notes' => $validated['organizer_notes'] ?? null,
                'requested_by' => $request->user()->id,
                'status' => VenueRequestStatus::Pending,
            ]);
        });

        return redirect()->route('venue-requests.index')->with('success', 'Venue request submitted for administrator approval.');
    }

    public function approve(Request $request, VenueRequest $venueRequest)
    {
        DB::transaction(function () use ($request, $venueRequest): void {
            $venueRequest = VenueRequest::with(['event', 'venue', 'timeslot'])->lockForUpdate()->findOrFail($venueRequest->id);
            abort_unless($venueRequest->status === VenueRequestStatus::Pending, 422, 'Only pending requests can be approved.');
            $this->constraints->validate($venueRequest->event, $venueRequest->venue, $venueRequest->timeslot);

            EventSchedule::create([
                'event_id' => $venueRequest->event_id,
                'venue_id' => $venueRequest->venue_id,
                'timeslot_id' => $venueRequest->timeslot_id,
                'status' => 'manual',
            ]);
            $venueRequest->update([
                'status' => VenueRequestStatus::Approved,
                'admin_notes' => $request->input('admin_notes'),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $venueRequest->event->update(['status' => EventStatus::Scheduled]);
        });

        return back()->with('success', 'Venue request approved and the event schedule was created.');
    }

    public function reject(Request $request, VenueRequest $venueRequest)
    {
        abort_unless($venueRequest->status === VenueRequestStatus::Pending, 422, 'Only pending requests can be rejected.');
        $validated = $request->validate(['admin_notes' => ['required', 'string', 'max:2000']]);
        $venueRequest->update($validated + [
            'status' => VenueRequestStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Venue request rejected. The organizer can submit another request.');
    }
}
