<x-layouts.app title="Automatic allocations">
    <x-page-header title="Automatic allocations" description="Review venue and time assignments created automatically from approved organizer requirements." />
    @if ($schedules->isEmpty())
        <x-empty-state title="No allocations yet" description="Allocations appear here after an organizer schedules an approved event." />
    @else
        <div class="space-y-4">
        @foreach ($schedules as $schedule)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="truncate text-lg font-semibold text-slate-900">{{ $schedule->event->title }}</h2><span class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">Automatically allocated</span></div><div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600"><span><strong class="text-slate-800">Venue:</strong> {{ $schedule->venue->name }}</span><span><strong class="text-slate-800">Date:</strong> {{ \Carbon\Carbon::parse($schedule->timeslot->slot_date)->format('d M Y') }}</span><span><strong class="text-slate-800">Time:</strong> {{ \Carbon\Carbon::parse($schedule->timeslot->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($schedule->timeslot->end_time)->format('g:i A') }}</span></div></div></article>
        @endforeach
        </div>
    @endif
</x-layouts.app>
