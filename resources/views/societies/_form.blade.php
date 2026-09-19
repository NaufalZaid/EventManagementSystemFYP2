@php($editing = isset($society))
<x-form-errors />
<form action="{{ $editing ? route('societies.update', $society) : route('societies.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-6 md:grid-cols-2">
        <div><label for="name" class="mb-2 block text-sm font-medium text-slate-700">Society name</label><input id="name" name="name" type="text" value="{{ old('name', $society->name ?? '') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Computing Society"></div>
        <div><label for="is_active" class="mb-2 block text-sm font-medium text-slate-700">Status</label><select id="is_active" name="is_active" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="1" @selected((string) old('is_active', isset($society) ? (int) $society->is_active : 1) === '1')>Active</option><option value="0" @selected((string) old('is_active', isset($society) ? (int) $society->is_active : 1) === '0')>Inactive</option></select></div>
        <div class="md:col-span-2"><label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label><textarea id="description" name="description" rows="4" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $society->description ?? '') }}</textarea></div>
        <div class="md:col-span-2 rounded-xl border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-900">Organizer assignment is handled separately</p><p class="mt-1 text-xs leading-5 text-indigo-700">Create this society first. Then open User Management to promote a registered student to organizer and select this society.</p></div>
    </div>
    <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('societies.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editing ? 'Update society' : 'Create society' }}</button></div>
</form>
