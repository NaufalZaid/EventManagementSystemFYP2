@php
    $editing = isset($schedule);
    $selectedDate = old('slot_date', $editing ? $schedule->timeslot->slot_date->format('Y-m-d') : '');
    $selectedStart = old('start_time', $editing ? substr($schedule->timeslot->start_time, 0, 5) : '');
    $selectedEnd = old('end_time', $editing ? substr($schedule->timeslot->end_time, 0, 5) : '');
@endphp
<x-form-errors />
<form action="{{ $editing ? route('schedules.update', $schedule) : route('schedules.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <input type="hidden" name="status" value="manual">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2"><label for="event_id" class="mb-2 block text-sm font-medium text-slate-700">Event</label><select id="event_id" name="event_id" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select an event</option>@foreach ($events as $event)<option value="{{ $event->id }}" data-extended="{{ $event->is_outside_working_hours ? '1' : '0' }}" @selected(old('event_id', $schedule->event_id ?? '') == $event->id)>{{ $event->title }} · {{ number_format($event->capacity) }} attendees · {{ $event->is_outside_working_hours ? 'extended hours' : '08:00–18:00' }}</option>@endforeach</select></div>
        <div><label for="venue_id" class="mb-2 block text-sm font-medium text-slate-700">Venue</label><select id="venue_id" name="venue_id" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select a venue</option>@foreach ($venues as $venue)<option value="{{ $venue->id }}" @selected(old('venue_id', $schedule->venue_id ?? '') == $venue->id)>{{ $venue->name }} · {{ number_format($venue->capacity) }} seats</option>@endforeach</select></div>
        <div><label for="slot_date" class="mb-2 block text-sm font-medium text-slate-700">Event date</label><input id="slot_date" name="slot_date" type="date" value="{{ $selectedDate }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
        <div><label for="start_time" class="mb-2 block text-sm font-medium text-slate-700">Start hour</label><select id="start_time" name="start_time" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select</option>@for($hour = 8; $hour < 23; $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" data-hour="{{ $hour }}" @selected($selectedStart === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}</option>@endfor</select></div>
        <div><label for="end_time" class="mb-2 block text-sm font-medium text-slate-700">End hour</label><select id="end_time" name="end_time" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select</option>@for($hour = 9; $hour <= 23; $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" data-hour="{{ $hour }}" @selected($selectedEnd === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}</option>@endfor</select></div>
    </div>
    <div id="hours-help" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm leading-6 text-indigo-800"><strong>Validation:</strong> times must be on the hour. The event must fit the venue and cannot overlap a booking or blackout.</div>
    <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('schedules.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editing ? 'Update schedule' : 'Create schedule' }}</button></div>
</form>
<script>
    const scheduleEvent = document.getElementById('event_id');
    const scheduleStart = document.getElementById('start_time');
    const scheduleEnd = document.getElementById('end_time');
    const hoursHelp = document.getElementById('hours-help');
    const syncScheduleHours = () => {
        const extended = scheduleEvent.selectedOptions[0]?.dataset.extended === '1';
        const closingHour = extended ? 23 : 18;
        scheduleStart.querySelectorAll('[data-hour]').forEach(option => option.disabled = Number(option.dataset.hour) >= closingHour);
        scheduleEnd.querySelectorAll('[data-hour]').forEach(option => option.disabled = Number(option.dataset.hour) > closingHour);
        if (scheduleStart.selectedOptions[0]?.disabled) scheduleStart.value = '';
        if (scheduleEnd.selectedOptions[0]?.disabled) scheduleEnd.value = '';
        hoursHelp.innerHTML = `<strong>Validation:</strong> times must be on the hour between 8:00 AM and ${extended ? '11:00 PM' : '6:00 PM'}. The event must fit the venue and cannot overlap a booking or blackout.`;
    };
    scheduleEvent.addEventListener('change', syncScheduleHours);
    syncScheduleHours();
</script>
