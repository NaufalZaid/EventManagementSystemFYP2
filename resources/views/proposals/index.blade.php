<x-layouts.app title="Event proposals">
    <x-page-header title="Event proposals" description="Review organizer submissions before venue requests are allowed." />
    @if ($events->isEmpty())
        <x-empty-state title="No proposals to review" description="Submitted proposals will appear here." />
    @else
        <div class="space-y-4">
            @foreach ($events as $event)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="font-semibold text-slate-900">{{ $event->title }}</h2><p class="mt-1 text-sm text-slate-500">{{ $event->organizer?->name ?? 'Unknown organizer' }} · {{ str($event->event_type)->headline() }}</p></div><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $event->status->label() }}</span></div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $event->description ?: 'No description provided.' }}</p>
                    <dl class="mt-4 flex flex-wrap gap-5 text-sm"><div><dt class="text-xs text-slate-500">Capacity</dt><dd class="font-semibold text-slate-800">{{ number_format($event->capacity) }}</dd></div><div><dt class="text-xs text-slate-500">Duration</dt><dd class="font-semibold text-slate-800">{{ $event->duration_minutes }} min</dd></div><div><dt class="text-xs text-slate-500">Committee</dt><dd class="font-semibold text-slate-800">{{ $event->committee ?: 'Not specified' }}</dd></div></dl>
                    @if ($event->status === \App\Enums\EventStatus::Submitted)
                        <div class="mt-5 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-2"><form action="{{ route('proposals.approve', $event) }}" method="POST">@csrf @method('PATCH')<button class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Approve proposal</button></form><form action="{{ route('proposals.reject', $event) }}" method="POST" class="flex gap-2">@csrf @method('PATCH')<input name="rejection_reason" required maxlength="2000" class="min-w-0 flex-1 rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Reason for returning proposal"><button class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Reject</button></form></div>
                    @elseif($event->rejection_reason)<p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $event->rejection_reason }}</p>@endif
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
