<x-layouts.app title="Societies">
    <x-page-header title="Societies" description="Manage organizer groups and their event ownership.">
        <a href="{{ route('societies.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create society</a>
    </x-page-header>

    <x-form-errors />
    @if ($societies->isEmpty())
        <x-empty-state title="No societies yet" description="Create a society before assigning organizer accounts."><a href="{{ route('societies.create') }}" class="text-sm font-semibold text-indigo-600">Create a society →</a></x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($societies as $society)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div><h2 class="font-semibold text-slate-900">{{ $society->name }}</h2><p class="mt-1 text-xs font-semibold {{ $society->is_active ? 'text-emerald-600' : 'text-red-600' }}">{{ $society->is_active ? 'Active' : 'Inactive' }}</p></div>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $society->organizers_count }} {{ Str::plural('organizer', $society->organizers_count) }}</span>
                    </div>
                    <p class="mt-4 min-h-10 text-sm leading-6 text-slate-600">{{ $society->description ?: 'No society description provided.' }}</p>
                    <p class="mt-3 text-xs text-slate-500">{{ $society->events_count }} {{ Str::plural('event', $society->events_count) }}</p>
                    <div class="mt-5 flex gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ route('societies.edit', $society) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a>
                        <a href="{{ route('users.index') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-purple-600 hover:bg-purple-50">Manage organizers</a>
                        <form action="{{ route('societies.destroy', $society) }}" method="POST">@csrf @method('DELETE')<button onclick="return confirm('Delete this unused society?')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
