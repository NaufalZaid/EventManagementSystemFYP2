<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\Request;

class EventController extends Controller
{
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
        $event = Event::create($this->validated($request) + [
            'organizer_id' => $request->user()->id,
            'status' => EventStatus::Draft,
        ]);

        return redirect()->route('events.edit', $event)->with('success', 'Draft created. Review it, then submit it for approval.');
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
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:100'],
            'committee' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'preferred_venue_id' => ['nullable', 'exists:venues,id'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_start_time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function authorizeManagement(Request $request, Event $event): void
    {
        abort_unless(
            $request->user()->hasRole('administrator') || $event->organizer_id === $request->user()->id,
            403
        );
    }
}
