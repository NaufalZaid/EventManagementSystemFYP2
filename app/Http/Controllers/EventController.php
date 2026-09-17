<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Venue;
use App\Services\SchedulingTimePolicy;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly SchedulingTimePolicy $timePolicy) {}

    public function index(Request $request)
    {
        $events = Event::with(['organizer', 'schedules.venue', 'schedules.timeslot'])
            ->withCount(['registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered)])
            ->when($request->user()->hasRole('organizer'), fn ($query) => $query->where('organizer_id', $request->user()->id))
            ->latest()
            ->get();

        return view('events.index', compact('events'));
    }

    public function create()
    {
        return view('events.create', ['venues' => Venue::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $isAdministrator = $request->user()->hasRole('administrator');
        $event = Event::create($this->validated($request) + [
            'organizer_id' => $request->user()->id,
            'status' => $isAdministrator ? EventStatus::Approved : EventStatus::Draft,
            'submitted_at' => $isAdministrator ? now() : null,
            'reviewed_at' => $isAdministrator ? now() : null,
            'reviewed_by' => $isAdministrator ? $request->user()->id : null,
        ]);

        $message = $isAdministrator
            ? 'Event created and approved. It can now be scheduled.'
            : 'Draft created. Review it, then submit it for approval.';

        return redirect()->route('events.edit', $event)->with('success', $message);
    }

    public function edit(Request $request, Event $event)
    {
        $this->authorizeManagement($request, $event);
        abort_if($event->status === EventStatus::Published, 422, 'Unpublish the event before editing it.');
        abort_unless($event->status->isEditable() || $request->user()->hasRole('administrator'), 403);

        return view('events.edit', ['event' => $event, 'venues' => Venue::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeManagement($request, $event);
        abort_if($event->status === EventStatus::Published, 422, 'Unpublish the event before editing it.');
        abort_unless($event->status->isEditable() || $request->user()->hasRole('administrator'), 403);
        $event->update($this->validated($request));

        return redirect()->route('events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorizeManagement($request, $event);
        abort_if($event->status === EventStatus::Published, 422, 'Unpublish the event before deleting it.');
        abort_unless($event->status->isEditable() || $request->user()->hasRole('administrator'), 403);
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:100'],
            'committee' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['required', 'integer', 'min:1'],
            'duration_minutes' => [
                'required',
                'integer',
                'min:60',
                'multiple_of:60',
                'max:'.($request->boolean('is_outside_working_hours') ? 900 : 600),
            ],
            'is_outside_working_hours' => ['nullable', 'boolean'],
            'preferred_venue_id' => ['nullable', 'exists:venues,id'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_start_time' => ['nullable', 'date_format:H:i'],
        ]);

        $validated['is_outside_working_hours'] = $request->boolean('is_outside_working_hours');

        if (! empty($validated['preferred_start_time'])) {
            $startHour = (int) substr($validated['preferred_start_time'], 0, 2);
            $this->timePolicy->validate(
                $validated['preferred_date'] ?? now()->toDateString(),
                $validated['preferred_start_time'],
                sprintf('%02d:00', $startHour + 1),
                $validated['is_outside_working_hours']
            );
        }

        return $validated;
    }

    private function authorizeManagement(Request $request, Event $event): void
    {
        abort_unless(
            $request->user()->hasRole('administrator') || $event->organizer_id === $request->user()->id,
            403
        );
    }
}
