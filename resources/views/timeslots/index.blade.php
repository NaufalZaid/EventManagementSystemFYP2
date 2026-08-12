<x-layouts.app title="Timeslots">
    <x-page-header title="Timeslots" description="Define the date and time windows available to event schedules."><a href="{{ route('timeslots.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create timeslot</a></x-page-header>
    @if ($timeslots->isEmpty())
        <x-empty-state title="No timeslots yet" description="Create a scheduling window before assigning events."><a href="{{ route('timeslots.create') }}" class="text-sm font-semibold text-indigo-600">Create a timeslot →</a></x-empty-state>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-600"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">Date</th><th class="px-6 py-4">Window</th><th class="px-6 py-4">Availability</th><th class="px-6 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
        @foreach ($timeslots as $timeslot)
            <tr class="hover:bg-slate-50/70"><td class="px-6 py-4 font-semibold text-slate-900">{{ \Carbon\Carbon::parse($timeslot->slot_date)->format('D, d M Y') }}</td><td class="px-6 py-4">{{ \Carbon\Carbon::parse($timeslot->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($timeslot->end_time)->format('g:i A') }}</td><td class="px-6 py-4"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span></td><td class="px-6 py-4"><div class="flex justify-end gap-2"><a href="{{ route('timeslots.edit', $timeslot) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a><form action="{{ route('timeslots.destroy', $timeslot) }}" method="POST">@csrf @method('DELETE')<button onclick="return confirm('Delete this timeslot and any related schedules?')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form></div></td></tr>
        @endforeach
        </tbody></table></div></div>
    @endif
</x-layouts.app>
