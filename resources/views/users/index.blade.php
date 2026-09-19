<x-layouts.app title="User management">
    <x-page-header title="User management" description="Assign application roles and connect event organizers to their society." />

    <form method="GET" action="{{ route('users.index') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_14rem_auto]">
        <div><label for="q" class="sr-only">Search users</label><input id="q" name="q" value="{{ request('q') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Search name or email"></div>
        <div><label for="role" class="sr-only">Filter by role</label><select id="role" name="role" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
    </form>

    @if($users->isEmpty())
        <x-empty-state title="No users found" description="Try adjusting the search or role filter." />
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">User</th><th class="px-6 py-4">Role</th><th class="px-6 py-4">Society</th><th class="px-6 py-4 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-50/70"><td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p></td><td class="px-6 py-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $user->role->label() }}</span></td><td class="px-6 py-4">{{ $user->society?->name ?? '—' }}</td><td class="px-6 py-4 text-right"><a href="{{ route('users.edit', $user) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Manage access</a></td></tr>
                @endforeach
            </tbody>
        </table></div></div>
    @endif
</x-layouts.app>
