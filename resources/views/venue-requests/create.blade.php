<x-layouts.app title="Request a venue">
    <x-page-header title="Request a venue" description="Choose a venue and timeslot for an approved event." />
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <x-form-errors />
        @if ($events->isEmpty() || $venues->isEmpty() || $timeslots->isEmpty())
            <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">You need an approved event without an active request, plus at least one active venue and timeslot.</div>
        @else
            <form action="{{ route('venue-requests.store') }}" method="POST" class="space-y-6">@csrf
                <div><label for="event_id" class="mb-2 block text-sm font-medium text-slate-700">Approved event</label><select id="event_id" name="event_id" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm">@foreach($events as $event)<option value="{{ $event->id }}" @selected((int) old('event_id', request('event_id')) === $event->id)>{{ $event->title }} · {{ $event->capacity }} people · {{ $event->duration_minutes }} min</option>@endforeach</select></div>
                <div class="grid gap-6 md:grid-cols-2"><div><label for="venue_id" class="mb-2 block text-sm font-medium text-slate-700">Venue</label><select id="venue_id" name="venue_id" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm">@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('venue_id') == $venue->id)>{{ $venue->name }} · {{ $venue->capacity }} seats</option>@endforeach</select></div><div><label for="timeslot_id" class="mb-2 block text-sm font-medium text-slate-700">Timeslot</label><select id="timeslot_id" name="timeslot_id" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm">@foreach($timeslots as $timeslot)<option value="{{ $timeslot->id }}" @selected(old('timeslot_id') == $timeslot->id)>{{ $timeslot->slot_date->format('d M Y') }} · {{ substr($timeslot->start_time, 0, 5) }}–{{ substr($timeslot->end_time, 0, 5) }}</option>@endforeach</select></div></div>
                <div><label for="organizer_notes" class="mb-2 block text-sm font-medium text-slate-700">Notes for the administrator</label><textarea id="organizer_notes" name="organizer_notes" rows="4" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Setup, equipment, or access requirements">{{ old('organizer_notes') }}</textarea></div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><a href="{{ route('venue-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Submit request</button></div>
            </form>
        @endif
    </div>
</x-layouts.app>
