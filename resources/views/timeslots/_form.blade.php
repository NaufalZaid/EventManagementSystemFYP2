@php($editing = isset($timeslot))
<x-form-errors />
<form action="{{ $editing ? route('timeslots.update', $timeslot) : route('timeslots.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-6 md:grid-cols-3">
        <div><label for="slot_date" class="mb-2 block text-sm font-medium text-slate-700">Date</label><input id="slot_date" name="slot_date" type="date" value="{{ old('slot_date', $timeslot->slot_date ?? '') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
        <div><label for="start_time" class="mb-2 block text-sm font-medium text-slate-700">Start time</label><input id="start_time" name="start_time" type="time" value="{{ old('start_time', $timeslot->start_time ?? '') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
        <div><label for="end_time" class="mb-2 block text-sm font-medium text-slate-700">End time</label><input id="end_time" name="end_time" type="time" value="{{ old('end_time', $timeslot->end_time ?? '') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">A timeslot represents a reusable scheduling window. Venue conflicts are checked when an event schedule is created.</div>
    <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('timeslots.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editing ? 'Update timeslot' : 'Create timeslot' }}</button></div>
</form>
