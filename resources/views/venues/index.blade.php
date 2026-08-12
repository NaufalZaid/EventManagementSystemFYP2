<x-layouts.app title="Venues">
    <x-page-header title="Venues" description="Maintain spaces available for university events."><a href="{{ route('venues.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create venue</a></x-page-header>
    @if ($venues->isEmpty())
        <x-empty-state title="No venues yet" description="Add a venue to make it available for scheduling."><a href="{{ route('venues.create') }}" class="text-sm font-semibold text-indigo-600">Create a venue →</a></x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($venues as $venue)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-start justify-between gap-4"><div><h2 class="font-semibold text-slate-900">{{ $venue->name }}</h2><p class="mt-1 text-xs text-slate-500">{{ $venue->location ?: 'Location not specified' }}</p></div><div class="text-right"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ number_format($venue->capacity) }} seats</span><p class="mt-2 text-xs font-semibold {{ $venue->is_active ? 'text-emerald-600' : 'text-red-600' }}">{{ $venue->is_active ? 'Active' : 'Inactive' }}</p></div></div><p class="mt-4 min-h-10 text-sm leading-6 text-slate-600">{{ $venue->description ?: 'No venue description provided.' }}</p><div class="mt-5 flex gap-2 border-t border-slate-100 pt-4"><a href="{{ route('venues.edit', $venue) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a><a href="{{ route('venues.blackouts.index', $venue) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50">Blackouts</a><form action="{{ route('venues.destroy', $venue) }}" method="POST">@csrf @method('DELETE')<button onclick="return confirm('Delete this venue and any related schedules?')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form></div></article>
        @endforeach
        </div>
    @endif
</x-layouts.app>
