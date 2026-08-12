<x-layouts.app title="Schedules">
    <x-page-header title="Schedules" description="Review current event, venue, and timeslot assignments."><a href="{{ route('schedules.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create schedule</a></x-page-header>
    @if ($schedules->isEmpty())
        <x-empty-state title="No schedules yet" description="Create a manual assignment after adding events, venues, and timeslots."><a href="{{ route('schedules.create') }}" class="text-sm font-semibold text-indigo-600">Create a schedule →</a></x-empty-state>
    @else
        <div class="space-y-4">
        @foreach ($schedules as $schedule)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="truncate text-lg font-semibold text-slate-900">{{ $schedule->event->title }}</h2><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $schedule->status === 'generated' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">{{ ucfirst($schedule->status) }}</span></div><div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600"><span><strong class="text-slate-800">Venue:</strong> {{ $schedule->venue->name }}</span><span><strong class="text-slate-800">Date:</strong> {{ \Carbon\Carbon::parse($schedule->timeslot->slot_date)->format('d M Y') }}</span><span><strong class="text-slate-800">Time:</strong> {{ \Carbon\Carbon::parse($schedule->timeslot->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($schedule->timeslot->end_time)->format('g:i A') }}</span></div></div><div class="flex shrink-0 gap-2"><a href="{{ route('schedules.edit', $schedule) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a><form action="{{ route('schedules.destroy', $schedule) }}" method="POST">@csrf @method('DELETE')<button onclick="return confirm('Delete this schedule?')" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form></div></div></article>
        @endforeach
        </div>
    @endif
</x-layouts.app>
