@php($editing = isset($venue))
<x-form-errors />
<form action="{{ $editing ? route('venues.update', $venue) : route('venues.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-6 md:grid-cols-2">
        <div><label for="name" class="mb-2 block text-sm font-medium text-slate-700">Venue name</label><input id="name" name="name" type="text" value="{{ old('name', $venue->name ?? '') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Main Hall"></div>
        <div><label for="location" class="mb-2 block text-sm font-medium text-slate-700">Location</label><input id="location" name="location" type="text" value="{{ old('location', $venue->location ?? '') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Building and floor"></div>
        <div><label for="capacity" class="mb-2 block text-sm font-medium text-slate-700">Maximum capacity</label><input id="capacity" name="capacity" type="number" min="0" value="{{ old('capacity', $venue->capacity ?? 0) }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
        <div><label for="is_active" class="mb-2 block text-sm font-medium text-slate-700">Availability</label><select id="is_active" name="is_active" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="1" @selected((string) old('is_active', isset($venue) ? (int) $venue->is_active : 1) === '1')>Active</option><option value="0" @selected((string) old('is_active', isset($venue) ? (int) $venue->is_active : 1) === '0')>Inactive</option></select></div>
        <div class="md:col-span-2"><label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label><textarea id="description" name="description" rows="4" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Facilities, accessibility, or usage notes.">{{ old('description', $venue->description ?? '') }}</textarea></div>
    </div>
    <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('venues.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editing ? 'Update venue' : 'Create venue' }}</button></div>
</form>
