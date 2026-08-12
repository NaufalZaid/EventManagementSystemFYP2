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

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="font-semibold text-slate-900">Organizer workflow</h2><div class="mt-5 grid gap-4 md:grid-cols-4">@foreach ([['1', 'Draft event', 'Add complete requirements'], ['2', 'Submit proposal', 'Administrator review'], ['3', 'Request venue', 'Choose an open slot'], ['4', 'Receive schedule', 'Created on approval']] as [$number, $title, $status])<div class="rounded-xl bg-slate-50 p-4"><span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $number }}</span><p class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</p><p class="mt-1 text-xs text-slate-500">{{ $status }}</p></div>@endforeach</div></section>
</x-layouts.app>
