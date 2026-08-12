<x-layouts.app title="Discover events">
    <x-page-header title="Discover events" description="Find published MMU events and reserve your place." />

    <form method="GET" action="{{ route('discover.index') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <div class="md:col-span-2"><label for="q" class="sr-only">Search events</label><input id="q" name="q" value="{{ request('q') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Search title, committee, or description"></div>
        <div><label for="type" class="sr-only">Event type</label><select id="type" name="type" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">All event types</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
        <div class="flex gap-2"><label for="date" class="sr-only">Event date</label><input id="date" name="date" type="date" value="{{ request('date') }}" class="min-w-0 flex-1 rounded-lg border border-slate-300 p-2.5 text-sm"><button class="rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white">Filter</button></div>
    </form>

    @if ($events->isEmpty())
        <x-empty-state title="No events found" description="Try adjusting your search filters or check again after more events are published." />
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($events as $event)
                @php($schedule = $event->schedules->first())
                @php($remaining = max(0, $event->capacity - $event->registered_count))
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ str($event->event_type)->headline() }}</span><span class="text-xs font-semibold {{ $remaining ? 'text-emerald-600' : 'text-red-600' }}">{{ $remaining ? $remaining.' places left' : 'Full' }}</span></div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $event->title }}</h2><p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $event->description ?: 'No description provided.' }}</p>
                    <dl class="mt-5 space-y-2 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Date</dt><dd class="font-medium text-slate-800">{{ $schedule->timeslot->slot_date->format('d M Y') }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Time</dt><dd class="font-medium text-slate-800">{{ substr($schedule->timeslot->start_time, 0, 5) }}–{{ substr($schedule->timeslot->end_time, 0, 5) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Venue</dt><dd class="text-right font-medium text-slate-800">{{ $schedule->venue->name }}</dd></div></dl>
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-5"><a href="{{ route('discover.show', $event) }}" class="text-sm font-semibold text-indigo-600">View details</a>@if($registeredEventIds->contains($event->id))<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Registered</span>@elseif($remaining)<form action="{{ route('events.register', $event) }}" method="POST">@csrf<button class="rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white">Register</button></form>@endif</div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
