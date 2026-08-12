<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventPlanningController extends Controller
{
    public function show(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);
        $event->load(['tasks' => fn ($query) => $query->orderBy('completed_at')->orderBy('due_date'), 'announcements' => fn ($query) => $query->latest('published_at')])
            ->loadCount(['registrations as registered_count' => fn ($query) => $query->where('status', 'registered')]);

        return view('planning.show', compact('event'));
    }

    public static function authorizeEvent(Request $request, Event $event): void
    {
        abort_unless($request->user()->hasRole('administrator') || $event->organizer_id === $request->user()->id, 403);
    }
}
