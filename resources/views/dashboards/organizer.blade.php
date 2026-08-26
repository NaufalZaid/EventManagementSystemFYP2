<x-layouts.app title="Organizer dashboard">
    <x-page-header title="Organizer dashboard" description="Move event ideas from draft through approval and venue assignment.">
        <a href="{{ route('events.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create event</a>
    </x-page-header>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Events</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $eventCount }}</p><a href="{{ route('events.index') }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-600">Manage events →</a></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Scheduled</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $scheduledCount }}</p><p class="mt-3 text-xs text-slate-500">Administrator-managed assignments</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Pending proposals</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $pendingCount }}</p><a href="{{ route('events.index') }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-600">Track proposals →</a></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Open tasks</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $openTaskCount }}</p><a href="{{ route('events.index') }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-600">Open event planning →</a></div>
    </div>

</x-layouts.app>
