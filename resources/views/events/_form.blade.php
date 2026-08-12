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
            <label for="duration_minutes" class="mb-2 block text-sm font-medium text-slate-700">Duration (minutes)</label>
            <input id="duration_minutes" name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes', $event->duration_minutes ?? '') }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="60">
        </div>
        <div class="md:col-span-2">
            <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
            <textarea id="description" name="description" rows="5" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe the event and its requirements.">{{ old('description', $event->description ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2 rounded-xl border border-purple-200 bg-purple-50 p-5"><h3 class="text-sm font-semibold text-purple-900">Optimizer preferences</h3><p class="mt-1 text-xs text-purple-700">Optional soft constraints. The GA attempts to satisfy these after all hard constraints.</p><div class="mt-4 grid gap-4 md:grid-cols-3"><div><label for="preferred_venue_id" class="mb-2 block text-sm font-medium text-slate-700">Preferred venue</label><select id="preferred_venue_id" name="preferred_venue_id" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"><option value="">No preference</option>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('preferred_venue_id', $event->preferred_venue_id ?? null) == $venue->id)>{{ $venue->name }}</option>@endforeach</select></div><div><label for="preferred_date" class="mb-2 block text-sm font-medium text-slate-700">Preferred date</label><input id="preferred_date" name="preferred_date" type="date" value="{{ old('preferred_date', isset($event) ? $event->preferred_date?->format('Y-m-d') : '') }}" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"></div><div><label for="preferred_start_time" class="mb-2 block text-sm font-medium text-slate-700">Preferred start</label><input id="preferred_start_time" name="preferred_start_time" type="time" value="{{ old('preferred_start_time', isset($event) ? substr((string) $event->preferred_start_time, 0, 5) : '') }}" class="block w-full rounded-lg border border-purple-200 bg-white p-2.5 text-sm"></div></div></div>
    </div>
    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
        <a href="{{ route('events.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">{{ $editing ? 'Update event' : 'Create event' }}</button>
    </div>
</form>
