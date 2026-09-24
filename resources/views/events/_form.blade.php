@php($editing = isset($event))
<x-form-errors />
<form action="{{ $editing ? route('events.update', $event) : route('events.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-6 md:grid-cols-2">
        @if(auth()->user()->hasRole('administrator'))
            <div class="md:col-span-2"><label for="society_id" class="mb-2 block text-sm font-medium text-slate-700">Owning society</label><select id="society_id" name="society_id" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"><option value="">University administration / no society</option>@foreach($societies as $society)<option value="{{ $society->id }}" @selected(old('society_id', $event->society_id ?? null) == $society->id)>{{ $society->name }}</option>@endforeach</select></div>
        @else
            <div class="md:col-span-2 rounded-xl border border-indigo-200 bg-indigo-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Owning society</p><p class="mt-1 font-medium text-indigo-950">{{ auth()->user()->society->name }}</p></div>
        @endif
        <div class="md:col-span-2">
            <label for="title" class="mb-2 block text-sm font-medium text-slate-700">Event title</label>
            <input id="title" name="title" type="text" value="{{ old('title', $event->title ?? '') }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Technology Career Fair">
        </div>
        <div class="md:col-span-2">
            <label for="event_type" class="mb-2 block text-sm font-medium text-slate-700">Event type</label>
            <input id="event_type" name="event_type" type="text" value="{{ old('event_type', $event->event_type ?? 'general') }}" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Workshop, seminar, competition">
        </div>
        <div class="md:col-span-2">
            <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
            <textarea id="description" name="description" rows="5" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe the event and its requirements.">{{ old('description', $event->description ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800"><strong>Proposal stage:</strong> submit the event idea for administrator approval first. After approval, you will enter the date, start time, duration, and expected attendance; the system will then allocate a venue automatically.</div>
    </div>
    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
        <a href="{{ route('events.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">{{ $editing ? 'Update event' : 'Create event' }}</button>
    </div>
</form>
