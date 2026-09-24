<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\Request;

class EventProposalController extends Controller
{
    public function index()
    {
        $events = Event::with(['organizer', 'society', 'reviewer'])
            ->whereIn('status', [EventStatus::Submitted, EventStatus::Approved, EventStatus::Rejected])
            ->latest('submitted_at')
            ->get();

        return view('proposals.index', compact('events'));
    }

    public function submit(Request $request, Event $event)
    {
        abort_unless($request->user()->canManageEvent($event), 403);
        abort_unless($event->status->isEditable(), 422, 'Only draft or rejected events can be submitted.');

        $event->update([
            'status' => EventStatus::Submitted,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->route('events.index')->with('success', 'Event submitted for administrator review.');
    }

    public function approve(Request $request, Event $event)
    {
        abort_unless($event->status === EventStatus::Submitted, 422, 'Only submitted events can be approved.');
        $event->update([
            'status' => EventStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Event proposal approved. The organizer may now enter its schedule requirements for automatic venue allocation.');
    }

    public function reject(Request $request, Event $event)
    {
        abort_unless($event->status === EventStatus::Submitted, 422, 'Only submitted events can be rejected.');
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $event->update($validated + [
            'status' => EventStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Event proposal returned to the organizer.');
    }
}
