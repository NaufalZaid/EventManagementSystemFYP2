<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\VenueBlackout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'all_day' => ['nullable', 'boolean'],
            'all_venues' => ['nullable', 'boolean'],
            'blackout_date' => ['required_if:all_day,1', 'nullable', 'date'],
            'starts_on' => ['required_unless:all_day,1', 'nullable', 'date'],
            'start_time' => ['required_unless:all_day,1', 'nullable', 'date_format:H:i'],
            'ends_on' => ['required_unless:all_day,1', 'nullable', 'date'],
            'end_time' => ['required_unless:all_day,1', 'nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($request->boolean('all_day')) {
            $startsAt = Carbon::parse($validated['blackout_date'])->startOfDay();
            $endsAt = $startsAt->copy()->addDay();
        } else {
            $startsAt = Carbon::parse($validated['starts_on'].' '.$validated['start_time']);
            $endsAt = Carbon::parse($validated['ends_on'].' '.$validated['end_time']);
            if ($startsAt->minute !== 0 || $startsAt->second !== 0 || $endsAt->minute !== 0 || $endsAt->second !== 0) {
                throw ValidationException::withMessages([
                    'start_time' => 'Blackout start and end times must be on the hour.',
                ]);
            }
            if ($startsAt->hour < 8 || $startsAt->hour >= 23) {
                throw ValidationException::withMessages([
                    'start_time' => 'The blackout start hour must be between 08:00 and 22:00.',
                ]);
            }
            if ($endsAt->hour < 8 || $endsAt->hour > 23) {
                throw ValidationException::withMessages([
                    'end_time' => 'The blackout end hour must be between 08:00 and 23:00.',
                ]);
            }
            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_on' => 'The blackout end must be after its start.',
                ]);
            }
        }

        $venues = $request->boolean('all_venues') ? Venue::all() : collect([$venue]);
        DB::transaction(function () use ($venues, $startsAt, $endsAt, $validated): void {
            foreach ($venues as $targetVenue) {
                $targetVenue->blackouts()->create([
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'reason' => $validated['reason'],
                ]);
            }
        });

        $message = $request->boolean('all_venues')
            ? 'Blackout added to all venues.'
            : 'Venue blackout period added.';

        return back()->with('success', $message);
    }

    public function destroy(Venue $venue, VenueBlackout $blackout)
    {
        abort_unless($blackout->venue_id === $venue->id, 404);
        $blackout->delete();

        return back()->with('success', 'Venue blackout period removed.');
    }
}
