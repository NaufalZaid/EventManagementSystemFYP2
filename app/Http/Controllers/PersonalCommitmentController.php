<?php

namespace App\Http\Controllers;

use App\Models\PersonalCommitment;
use App\Services\CalendarConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PersonalCommitmentController extends Controller
{
    public function __construct(private readonly CalendarConflictService $conflicts) {}

    public function create()
    {
        return view('commitments.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $conflicts = $this->conflicts->conflicts(
            $request->user(), Carbon::parse($validated['starts_at']), Carbon::parse($validated['ends_at'])
        );
        $commitment = $request->user()->personalCommitments()->create($validated);

        return $this->calendarRedirect($commitment, 'Commitment added to your calendar.', $conflicts);
    }

    public function edit(Request $request, PersonalCommitment $commitment)
    {
        $this->authorizeOwner($request, $commitment);

        return view('commitments.edit', compact('commitment'));
    }

    public function update(Request $request, PersonalCommitment $commitment)
    {
        $this->authorizeOwner($request, $commitment);
        $validated = $this->validated($request);
        $conflicts = $this->conflicts->conflicts(
            $request->user(), Carbon::parse($validated['starts_at']), Carbon::parse($validated['ends_at']), $commitment
        );
        $commitment->update($validated);

        return $this->calendarRedirect($commitment, 'Commitment updated.', $conflicts);
    }

    public function destroy(Request $request, PersonalCommitment $commitment)
    {
        $this->authorizeOwner($request, $commitment);
        $month = $commitment->starts_at->format('Y-m');
        $commitment->delete();

        return redirect()->route('calendar.index', ['month' => $month])->with('success', 'Commitment removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'commitment_type' => ['required', 'in:class,test,meeting,study,personal'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
    }

    private function authorizeOwner(Request $request, PersonalCommitment $commitment): void
    {
        abort_unless($commitment->user_id === $request->user()->id, 403);
    }

    private function calendarRedirect(PersonalCommitment $commitment, string $message, array $conflicts)
    {
        $redirect = redirect()->route('calendar.index', ['month' => $commitment->starts_at->format('Y-m')])
            ->with('success', $message);

        if ($conflicts !== []) {
            $redirect->with('warning', 'Schedule clash detected with '.implode(', ', $conflicts).'.');
        }

        return $redirect;
    }
}
