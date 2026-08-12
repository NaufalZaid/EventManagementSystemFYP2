<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use Illuminate\Http\Request;

class EventDiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
        ]);

        $events = Event::query()
            ->with(['organizer', 'schedules.venue', 'schedules.timeslot'])
            ->withCount(['registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered)])
            ->where('status', EventStatus::Published)
            ->whereHas('schedules')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('committee', 'like', $term);
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('event_type', $request->string('type')))
            ->when($request->filled('date'), fn ($query) => $query->whereHas('schedules.timeslot', fn ($query) => $query->whereDate('slot_date', $request->date('date'))))
            ->get()
            ->sortBy(fn (Event $event) => $event->schedules->first()?->timeslot?->slot_date?->format('Y-m-d').' '.$event->schedules->first()?->timeslot?->start_time);

        $types = Event::where('status', EventStatus::Published)->distinct()->orderBy('event_type')->pluck('event_type');
        $registeredEventIds = $request->user()->eventRegistrations()
            ->where('status', RegistrationStatus::Registered)
            ->pluck('event_id');

        return view('discovery.index', compact('events', 'types', 'registeredEventIds'));
    }

    public function show(Request $request, Event $event)
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $event->load(['organizer', 'schedules.venue', 'schedules.timeslot'])
            ->loadCount(['registrations as registered_count' => fn ($query) => $query->where('status', RegistrationStatus::Registered)]);
        $isRegistered = $event->registrations()->where('user_id', $request->user()->id)
            ->where('status', RegistrationStatus::Registered)->exists();

        return view('discovery.show', compact('event', 'isRegistered'));
    }
}
