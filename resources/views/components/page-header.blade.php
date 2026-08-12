@props(['title', 'description' => null])

<div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-indigo-600">Event Management System</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="shrink-0">{{ $slot }}</div>
    @endif
</div>
