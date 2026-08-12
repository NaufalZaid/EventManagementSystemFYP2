<x-layouts.app title="Events">
    <x-page-header title="Events" description="Draft, submit, review, and schedule university event proposals.">
        <a href="{{ route('events.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create event</a>
    </x-page-header>
    @if ($events->isEmpty())
        <x-empty-state title="No events yet" description="Create an event draft to begin the approval workflow."><a href="{{ route('events.create') }}" class="text-sm font-semibold text-indigo-600">Create an event →</a></x-empty-state>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">Event</th><th class="px-6 py-4">Owner</th><th class="px-6 py-4">Requirements</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach ($events as $event)
                @php($editable = $event->status !== \App\Enums\EventStatus::Published && ($event->status->isEditable() || auth()->user()->hasRole('administrator')))
                <tr class="align-top hover:bg-slate-50/70">
                    <td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $event->title }}</p><p class="mt-1 text-xs text-slate-500">{{ str($event->event_type)->headline() }}{{ $event->committee ? ' · '.$event->committee : '' }}</p>@if($event->rejection_reason)<p class="mt-2 max-w-md text-xs text-red-600">Returned: {{ $event->rejection_reason }}</p>@endif</td>
                    <td class="px-6 py-4">{{ $event->organizer?->name ?? 'Legacy record' }}</td>
                    <td class="px-6 py-4">{{ number_format($event->capacity) }} people<br><span class="text-xs text-slate-500">{{ $event->duration_minutes }} minutes</span>@if($event->status === \App\Enums\EventStatus::Published)<br><span class="text-xs font-medium text-emerald-600">{{ $event->registered_count }} registered</span>@endif</td>
                    <td class="px-6 py-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $event->status->label() }}</span>@if($event->schedules->first())<p class="mt-2 text-xs text-slate-500">{{ $event->schedules->first()->venue->name }}</p>@endif</td>
                    <td class="px-6 py-4"><div class="flex flex-wrap justify-end gap-2">
                        @if ($editable)<a href="{{ route('events.edit', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a>@endif
                        <a href="{{ route('events.planning', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-purple-600 hover:bg-purple-50">Plan</a>
                        @if(in_array($event->status, [\App\Enums\EventStatus::Published, \App\Enums\EventStatus::Completed], true))<a href="{{ route('events.attendance.show', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-emerald-600 hover:bg-emerald-50">Attendance</a>@endif
                        @if (auth()->user()->hasRole('organizer') && $event->status->isEditable())<form action="{{ route('events.submit', $event) }}" method="POST">@csrf<button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Submit</button></form>@endif
                        @if (auth()->user()->hasRole('organizer') && $event->status === \App\Enums\EventStatus::Approved)<a href="{{ route('venue-requests.create', ['event_id' => $event->id]) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Request venue</a>@endif
                        @if (auth()->user()->hasRole('administrator') && $event->status === \App\Enums\EventStatus::Scheduled)<form action="{{ route('events.publish', $event) }}" method="POST">@csrf @method('PATCH')<button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Publish</button></form>@endif
                        @if (auth()->user()->hasRole('administrator') && $event->status === \App\Enums\EventStatus::Published)<form action="{{ route('events.unpublish', $event) }}" method="POST">@csrf @method('PATCH')<button class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600">Unpublish</button></form>@endif
                        @if ($editable)<form action="{{ route('events.destroy', $event) }}" method="POST">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Delete this event and its related records?')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form>@endif
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div></div>
    @endif
</x-layouts.app>
