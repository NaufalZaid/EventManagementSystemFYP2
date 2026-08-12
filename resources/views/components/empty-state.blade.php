@props(['title', 'description'])

<div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-indigo-50 text-indigo-600">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
    </div>
    <h2 class="mt-4 font-semibold text-slate-900">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ $description }}</p>
    <div class="mt-5">{{ $slot }}</div>
</div>
