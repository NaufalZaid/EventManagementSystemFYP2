<x-layouts.app title="Venue blackouts">
    <x-page-header title="{{ $venue->name }} blackouts" description="Block full days or hour-based periods for this venue or every active venue." />
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Add blackout</h2><x-form-errors />
            <form action="{{ route('venues.blackouts.store', $venue) }}" method="POST" class="mt-5 space-y-4">@csrf
                <label class="flex items-start gap-3 rounded-lg bg-amber-50 p-3"><input id="all_venues" name="all_venues" type="checkbox" value="1" @checked(old('all_venues')) class="mt-0.5 rounded border-amber-300 text-amber-600"><span class="text-sm text-amber-900"><strong>Apply to all venues</strong><br><span class="text-xs text-amber-700">Useful for public holidays and university closures.</span></span></label>
                <label class="flex items-center gap-3"><input id="all_day" name="all_day" type="checkbox" value="1" @checked(old('all_day')) class="rounded border-slate-300 text-indigo-600"><span class="text-sm font-medium text-slate-700">Block the entire day</span></label>
                <div id="all-day-fields"><label class="mb-2 block text-sm font-medium" for="blackout_date">Blackout date</label><input id="blackout_date" type="date" name="blackout_date" value="{{ old('blackout_date') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
                <div id="hour-fields" class="space-y-4"><div><label class="mb-2 block text-sm font-medium" for="starts_at">Starts (on the hour)</label><input id="starts_at" type="datetime-local" step="3600" name="starts_at" value="{{ old('starts_at') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div><div><label class="mb-2 block text-sm font-medium" for="ends_at">Ends (on the hour)</label><input id="ends_at" type="datetime-local" step="3600" name="ends_at" value="{{ old('ends_at') }}" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div></div>
                <div><label class="mb-2 block text-sm font-medium" for="reason">Reason</label><input id="reason" name="reason" value="{{ old('reason') }}" required maxlength="255" class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="Public holiday or maintenance"></div>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Add blackout</button>
            </form>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3"><h2 class="font-semibold text-slate-900">Unavailable periods</h2>@if($blackouts->isEmpty())<p class="mt-4 text-sm text-slate-500">No blackout periods recorded.</p>@else<div class="mt-4 divide-y divide-slate-100">@foreach($blackouts as $blackout)<div class="flex items-center justify-between gap-4 py-4"><div><p class="text-sm font-semibold text-slate-800">{{ $blackout->reason }}</p><p class="mt-1 text-xs text-slate-500">{{ $blackout->starts_at->format('d M Y, H:i') }} – {{ $blackout->ends_at->format('d M Y, H:i') }}</p></div><form action="{{ route('venues.blackouts.destroy', [$venue, $blackout]) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600">Remove</button></form></div>@endforeach</div>@endif</section>
    </div>
    <script>
        const allDay = document.getElementById('all_day');
        const syncBlackoutFields = () => {
            document.getElementById('all-day-fields').classList.toggle('hidden', !allDay.checked);
            document.getElementById('hour-fields').classList.toggle('hidden', allDay.checked);
            document.getElementById('blackout_date').required = allDay.checked;
            document.getElementById('starts_at').required = !allDay.checked;
            document.getElementById('ends_at').required = !allDay.checked;
        };
        allDay.addEventListener('change', syncBlackoutFields);
        syncBlackoutFields();
    </script>
</x-layouts.app>
