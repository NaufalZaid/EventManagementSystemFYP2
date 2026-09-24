<x-layouts.app title="Events">
    <x-page-header title="Events" description="Draft, submit, review, and schedule university event proposals.">
        @if(auth()->user()->hasRole('organizer'))<a href="{{ route('events.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create event proposal</a>@endif
    </x-page-header>
    @if ($events->isEmpty())
        <x-empty-state title="No events yet" description="Create an event draft to begin the approval workflow."><a href="{{ route('events.create') }}" class="text-sm font-semibold text-indigo-600">Create an event →</a></x-empty-state>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">Event</th><th class="px-6 py-4">Society / creator</th><th class="px-6 py-4">Requirements</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach ($events as $event)
                @php($editable = auth()->user()->hasRole('organizer') && $event->status->isEditable())
                <tr class="align-top hover:bg-slate-50/70">
                    <td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $event->title }}</p><p class="mt-1 text-xs text-slate-500">{{ str($event->event_type)->headline() }}</p>@if($event->rejection_reason)<p class="mt-2 max-w-md text-xs text-red-600">Returned: {{ $event->rejection_reason }}</p>@endif</td>
                    <td class="px-6 py-4"><span class="font-medium text-slate-800">{{ $event->society?->name ?? 'University administration' }}</span><br><span class="text-xs text-slate-500">Created by {{ $event->organizer?->name ?? 'Legacy record' }}</span></td>
                    <td class="px-6 py-4">@if($event->duration_minutes){{ number_format($event->capacity) }} people<br><span class="text-xs text-slate-500">{{ $event->duration_minutes / 60 }} {{ Str::plural('hour', $event->duration_minutes / 60) }}</span>@else<span class="text-xs text-slate-500">Entered after approval</span>@endif @if($event->status === \App\Enums\EventStatus::Published)<br><span class="text-xs font-medium text-emerald-600">{{ $event->registered_count }} registered</span>@endif</td>
                    <td class="px-6 py-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $event->status->label() }}</span>@if($event->schedules->first())<p class="mt-2 text-xs text-slate-500">{{ $event->schedules->first()->venue->name }}</p>@endif</td>
                    <td class="px-6 py-4"><div class="flex flex-wrap justify-end gap-2">
                        @if ($editable)<a href="{{ route('events.edit', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a>@endif
                        <a href="{{ route('events.planning', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-purple-600 hover:bg-purple-50">Plan</a>
                        @if(in_array($event->status, [\App\Enums\EventStatus::Published, \App\Enums\EventStatus::Completed], true))<a href="{{ route('events.attendance.show', $event) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-emerald-600 hover:bg-emerald-50">Attendance</a>@endif
                        @if (auth()->user()->hasRole('organizer') && $event->status->isEditable())<form action="{{ route('events.submit', $event) }}" method="POST">@csrf<button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Submit</button></form>@endif
                        @if (auth()->user()->hasRole('organizer') && $event->status === \App\Enums\EventStatus::Approved)<a href="{{ route('events.allocation.create', $event) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Schedule automatically</a>@endif
                        @if ($editable)<form action="{{ route('events.destroy', $event) }}" method="POST">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Delete this event and its related records?')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form>@endif
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div></div>
    @endif
</x-layouts.app>
