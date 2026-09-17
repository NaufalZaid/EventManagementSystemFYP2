<x-layouts.app title="Request a venue">
    <x-page-header title="Request a venue" description="Choose your exact event hours, then select from venues that are available." />
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <x-form-errors />
        @if ($events->isEmpty())
            <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">You need an approved event without an active venue request.</div>
        @else
            <form action="{{ route('venue-requests.create') }}" method="GET" class="space-y-5">
                <div><label for="event_id" class="mb-2 block text-sm font-medium text-slate-700">Approved event</label><select id="event_id" name="event_id" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select an event</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected((int) old('event_id', request('event_id')) === $event->id)>{{ $event->title }} · {{ $event->capacity }} people · {{ $event->duration_minutes / 60 }} {{ Str::plural('hour', $event->duration_minutes / 60) }}{{ $event->is_outside_working_hours ? ' · evening permitted' : '' }}</option>@endforeach</select></div>
                <div class="grid gap-5 md:grid-cols-3">
                    <div><label for="slot_date" class="mb-2 block text-sm font-medium text-slate-700">Event date</label><input id="slot_date" name="slot_date" type="date" value="{{ old('slot_date', $slotDate) }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
                    <div><label for="start_time" class="mb-2 block text-sm font-medium text-slate-700">Start hour</label><select id="start_time" name="start_time" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select</option>@for($hour = 8; $hour < ($selectedEvent?->is_outside_working_hours ? 23 : 18); $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" @selected(old('start_time', $startTime) === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}</option>@endfor</select></div>
                    <div><label for="end_time" class="mb-2 block text-sm font-medium text-slate-700">End hour</label><select id="end_time" name="end_time" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select</option>@for($hour = 9; $hour <= ($selectedEvent?->is_outside_working_hours ? 23 : 18); $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" @selected(old('end_time', $endTime) === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}</option>@endfor</select></div>
                </div>
                <div class="flex items-center justify-between gap-4"><p class="text-xs text-slate-500">Times are selected by you in one-hour increments. {{ $selectedEvent?->is_outside_working_hours ? 'This event may finish by 11:00 PM.' : 'Normal hours are 8:00 AM–6:00 PM.' }}</p><button class="rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white">Check available venues</button></div>
            </form>

            @if($availabilityChecked)
                <div class="my-6 border-t border-slate-200"></div>
                @if($venues->isEmpty())
                    <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">No venue with enough capacity is available for these hours. Choose another date or time.</div>
                @else
                    <form action="{{ route('venue-requests.store') }}" method="POST" class="space-y-5">@csrf
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}"><input type="hidden" name="slot_date" value="{{ $slotDate }}"><input type="hidden" name="start_time" value="{{ $startTime }}"><input type="hidden" name="end_time" value="{{ $endTime }}">
                        <div><label for="venue_id" class="mb-2 block text-sm font-medium text-slate-700">Available venue</label><select id="venue_id" name="venue_id" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select a venue</option>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('venue_id') == $venue->id)>{{ $venue->name }} · {{ $venue->capacity }} seats</option>@endforeach</select><p class="mt-1.5 text-xs text-emerald-700">Unavailable, blacked-out, inactive, and undersized venues have been removed.</p></div>
                        <div><label for="organizer_notes" class="mb-2 block text-sm font-medium text-slate-700">Notes for the administrator</label><textarea id="organizer_notes" name="organizer_notes" rows="4" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Setup, equipment, or access requirements">{{ old('organizer_notes') }}</textarea></div>
                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><a href="{{ route('venue-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Submit request</button></div>
                    </form>
                @endif
            @endif
        @endif
    </div>
    <script>
        const eventSelect = document.getElementById('event_id');
        eventSelect?.addEventListener('change', () => {
            if (eventSelect.value) window.location = `{{ route('venue-requests.create') }}?event_id=${eventSelect.value}`;
        });
    </script>
</x-layouts.app>
