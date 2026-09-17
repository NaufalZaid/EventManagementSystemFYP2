@php($editing = isset($event))
<x-form-errors />
<form action="{{ $editing ? route('events.update', $event) : route('events.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="title" class="mb-2 block text-sm font-medium text-slate-700">Event title</label>
            <input id="title" name="title" type="text" value="{{ old('title', $event->title ?? '') }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Technology Career Fair">
        </div>
        <div>
            <label for="event_type" class="mb-2 block text-sm font-medium text-slate-700">Event type</label>
            <input id="event_type" name="event_type" type="text" value="{{ old('event_type', $event->event_type ?? 'general') }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Workshop, seminar, competition">
        </div>
        <div>
            <label for="committee" class="mb-2 block text-sm font-medium text-slate-700">Organizing committee</label>
            <input id="committee" name="committee" type="text" value="{{ old('committee', $event->committee ?? '') }}" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Society or committee name">
        </div>
        <div>
            <label for="capacity" class="mb-2 block text-sm font-medium text-slate-700">Expected capacity</label>
            <input id="capacity" name="capacity" type="number" min="0" value="{{ old('capacity', $event->capacity ?? 0) }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1.5 text-xs text-slate-500">Used to validate whether a venue is large enough.</p>
        </div>
        <div>
            <label for="duration_minutes" class="mb-2 block text-sm font-medium text-slate-700">Duration</label>
            <select id="duration_minutes" name="duration_minutes" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select duration</option>@for($hours = 1; $hours <= 15; $hours++)<option value="{{ $hours * 60 }}" @selected((int) old('duration_minutes', $event->duration_minutes ?? 0) === $hours * 60)>{{ $hours }} {{ Str::plural('hour', $hours) }}</option>@endfor</select>
            <p class="mt-1.5 text-xs text-slate-500">Durations use complete hours. Normal events can run for up to 10 hours; extended events up to 15 hours.</p>
        </div>
        <div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <label class="flex items-start gap-3" for="is_outside_working_hours"><input id="is_outside_working_hours" name="is_outside_working_hours" type="checkbox" value="1" @checked(old('is_outside_working_hours', $event->is_outside_working_hours ?? false)) class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500"><span><span class="block text-sm font-semibold text-amber-900">This event needs to run outside normal working hours</span><span class="mt-1 block text-xs text-amber-700">Normal events may run from 08:00 to 18:00. Select this for an evening event that may finish as late as 23:00.</span></span></label>
        </div>
        <div class="md:col-span-2">
            <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
            <textarea id="description" name="description" rows="5" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe the event and its requirements.">{{ old('description', $event->description ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2 rounded-xl border border-purple-200 bg-purple-50 p-5">
            <h3 class="text-sm font-semibold text-purple-900">Optimizer preferences</h3><p class="mt-1 text-xs text-purple-700">Optional soft constraints. The GA attempts to satisfy these after all hard constraints.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div><label for="preferred_venue_id" class="mb-2 block text-sm font-medium text-slate-700">Preferred venue</label><select id="preferred_venue_id" name="preferred_venue_id" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"><option value="">No preference</option>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('preferred_venue_id', $event->preferred_venue_id ?? null) == $venue->id)>{{ $venue->name }}</option>@endforeach</select></div>
                <div><label for="preferred_date" class="mb-2 block text-sm font-medium text-slate-700">Preferred date</label><input id="preferred_date" name="preferred_date" type="date" value="{{ old('preferred_date', isset($event) ? $event->preferred_date?->format('Y-m-d') : '') }}" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"></div>
                <div><label for="preferred_start_time" class="mb-2 block text-sm font-medium text-slate-700">Preferred start hour</label><select id="preferred_start_time" name="preferred_start_time" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"><option value="">No preference</option>@for($hour = 8; $hour < 23; $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" @selected(old('preferred_start_time', isset($event) ? substr((string) $event->preferred_start_time, 0, 5) : '') === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}</option>@endfor</select></div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
        <a href="{{ route('events.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">{{ $editing ? 'Update event' : 'Create event' }}</button>
    </div>
</form>
