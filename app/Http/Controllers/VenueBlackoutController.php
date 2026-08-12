<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\VenueBlackout;
use Illuminate\Http\Request;

class VenueBlackoutController extends Controller
{
    public function index(Venue $venue)
    {
        $blackouts = $venue->blackouts()->orderBy('starts_at')->get();

        return view('venues.blackouts', compact('venue', 'blackouts'));
    }

    public function store(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $venue->blackouts()->create($validated);

        return back()->with('success', 'Venue blackout period added.');
    }

    public function destroy(Venue $venue, VenueBlackout $blackout)
    {
        abort_unless($blackout->venue_id === $venue->id, 404);
        $blackout->delete();

        return back()->with('success', 'Venue blackout period removed.');
    }
}
