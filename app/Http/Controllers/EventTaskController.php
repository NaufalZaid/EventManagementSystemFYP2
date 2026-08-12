<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Http\Request;

class EventTaskController extends Controller
{
    public function store(Request $request, Event $event)
    {
        EventPlanningController::authorizeEvent($request, $event);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);
        $event->tasks()->create($validated);

        return back()->with('success', 'Preparation task added.');
    }

    public function toggle(Request $request, Event $event, EventTask $task)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($task->event_id === $event->id, 404);
        $task->update(['completed_at' => $task->completed_at ? null : now()]);

        return back()->with('success', $task->completed_at ? 'Task completed.' : 'Task reopened.');
    }

    public function destroy(Request $request, Event $event, EventTask $task)
    {
        EventPlanningController::authorizeEvent($request, $event);
        abort_unless($task->event_id === $event->id, 404);
        $task->delete();

        return back()->with('success', 'Preparation task removed.');
    }
}
