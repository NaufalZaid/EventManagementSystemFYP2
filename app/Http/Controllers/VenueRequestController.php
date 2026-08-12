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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenueRequestController extends Controller
{
    public function __construct(private readonly SchedulingConstraintService $constraints) {}

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
        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $timeslots = Timeslot::orderBy('slot_date')->orderBy('start_time')->get();

        return view('venue-requests.create', compact('events', 'venues', 'timeslots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'venue_id' => ['required', 'exists:venues,id'],
            'timeslot_id' => ['required', 'exists:timeslots,id'],
            'organizer_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $event = Event::findOrFail($validated['event_id']);
        abort_unless($event->organizer_id === $request->user()->id, 403);
        abort_unless($event->status === EventStatus::Approved, 422, 'Only approved events can request a venue.');
        abort_if($event->venueRequests()->whereIn('status', [VenueRequestStatus::Pending, VenueRequestStatus::Approved])->exists(), 422, 'This event already has an active venue request.');

        $venue = Venue::findOrFail($validated['venue_id']);
        $timeslot = Timeslot::findOrFail($validated['timeslot_id']);
        $this->constraints->validate($event, $venue, $timeslot);

        VenueRequest::create($validated + [
            'requested_by' => $request->user()->id,
            'status' => VenueRequestStatus::Pending,
        ]);

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
