<x-layouts.app title="Manage user access">
    <x-page-header title="Manage user access" description="Promote a registered user to organizer and assign their society." />

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
        <div class="mb-6 rounded-xl bg-slate-50 p-4"><p class="font-semibold text-slate-900">{{ $managedUser->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $managedUser->email }}</p></div>
        <x-form-errors />
        <form action="{{ route('users.update', $managedUser) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <div><label for="role" class="mb-2 block text-sm font-medium text-slate-700">Role</label><select id="role" name="role" required class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach($roles as $role)<option value="{{ $role->value }}" @selected(old('role', $managedUser->role->value) === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
            <div><label for="society_id" class="mb-2 block text-sm font-medium text-slate-700">Society</label><select id="society_id" name="society_id" class="block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select a society</option>@foreach($societies as $society)<option value="{{ $society->id }}" @selected(old('society_id', $managedUser->society_id) == $society->id)>{{ $society->name }}</option>@endforeach</select><p class="mt-1.5 text-xs text-slate-500">Required for organizer accounts.</p></div>
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Update access</button></div>
        </form>
    </div>
</x-layouts.app>
