<x-layouts.app title="Automatically schedule {{ $event->title }}">
    <x-page-header title="Schedule {{ $event->title }}" description="Enter the event requirements. The system will allocate the smallest suitable available venue automatically." />
    <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
        <x-form-errors />
        <form action="{{ route('events.allocation.store', $event) }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="slot_date" class="mb-2 block text-sm font-medium text-slate-700">Event date</label>
                    <input id="slot_date" name="slot_date" type="text" value="{{ old('slot_date') }}" required readonly autocomplete="off" placeholder="YYYY-MM-DD" data-weekdays-only data-min-date="{{ today()->toDateString() }}" aria-describedby="slot_date_help" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-2.5 text-sm">
                    <p id="slot_date_help" class="mt-1.5 text-xs text-slate-500">Weekdays only. Saturday and Sunday cannot be selected.</p>
                </div>
                <div><label for="start_time" class="mb-2 block text-sm font-medium text-slate-700">Start time</label><select id="start_time" name="start_time" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select</option>@for($hour = 8; $hour < 23; $hour++)<option value="{{ sprintf('%02d:00', $hour) }}" @selected(old('start_time') === sprintf('%02d:00', $hour))>{{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}</option>@endfor</select></div>
                <div><label for="duration_minutes" class="mb-2 block text-sm font-medium text-slate-700">Duration</label><select id="duration_minutes" name="duration_minutes" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Select</option>@for($hours = 1; $hours <= 15; $hours++)<option value="{{ $hours * 60 }}" @selected((int) old('duration_minutes') === $hours * 60)>{{ $hours }} {{ Str::plural('hour', $hours) }}</option>@endfor</select></div>
                <div><label for="capacity" class="mb-2 block text-sm font-medium text-slate-700">Expected attendance</label><input id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm" placeholder="e.g. 80"></div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('events.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><button class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Allocate venue and publish</button></div>
        </form>
    </div>
</x-layouts.app>
