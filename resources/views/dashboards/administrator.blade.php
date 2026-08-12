<x-layouts.app title="Administrator dashboard">
    <x-page-header title="Administrator dashboard" description="Review event activity and manage approvals, venues, timeslots, and schedules.">
        <a href="{{ route('schedules.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create schedule</a>
    </x-page-header>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([['Users', $userCount, null], ['Events', $eventCount, 'events.index'], ['Venues', $venueCount, 'venues.index'], ['Timeslots', $timeslotCount, 'timeslots.index'], ['Schedules', $scheduleCount, 'schedules.index']] as [$label, $value, $route])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</p>@if ($route)<a href="{{ route($route) }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-600">View {{ strtolower($label) }} →</a>@else<p class="mt-3 text-xs text-slate-500">Registered accounts</p>@endif</div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-2"><a href="{{ route('proposals.index') }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-6"><p class="text-sm font-semibold text-amber-800">Proposals awaiting review</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $proposalCount }}</p></a><a href="{{ route('venue-requests.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6"><p class="text-sm font-semibold text-emerald-800">Venue requests awaiting review</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $venueRequestCount }}</p></a></div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2"><div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Scheduling workspace</h2><p class="mt-1 text-sm text-slate-500">Maintain the data required for conflict-aware assignments.</p></div><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Operational</span></div><div class="mt-5 grid gap-3 sm:grid-cols-3"><a href="{{ route('venues.index') }}" class="rounded-xl border border-slate-200 p-4 text-sm font-semibold text-slate-800 hover:border-indigo-300 hover:bg-indigo-50">Manage venues</a><a href="{{ route('timeslots.index') }}" class="rounded-xl border border-slate-200 p-4 text-sm font-semibold text-slate-800 hover:border-indigo-300 hover:bg-indigo-50">Manage timeslots</a><a href="{{ route('schedules.index') }}" class="rounded-xl border border-slate-200 p-4 text-sm font-semibold text-slate-800 hover:border-indigo-300 hover:bg-indigo-50">Review schedules</a></div></section>
        <section class="rounded-2xl border border-purple-200 bg-purple-50 p-6"><span class="text-xs font-semibold uppercase tracking-wider text-purple-700">Schedule generation</span><h2 class="mt-2 font-semibold text-slate-900">GA schedule optimizer</h2><p class="mt-2 text-sm leading-6 text-slate-600">Generate candidate assignments, validate scheduling constraints, account for event preferences, and compare results with manual schedules.</p><a href="{{ route('optimizer.index') }}" class="mt-5 inline-flex rounded-lg bg-purple-600 px-3.5 py-2 text-xs font-semibold text-white">Open optimizer →</a></section>
    </div>
</x-layouts.app>
