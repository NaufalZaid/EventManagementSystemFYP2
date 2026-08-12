<x-layouts.app title="Overview">
    <x-page-header title="Build better university events" description="A centralized prototype for managing events, venues, timeslots, and conflict-aware schedules at Multimedia University." />

    <section class="overflow-hidden rounded-2xl bg-slate-950 shadow-sm">
        <div class="grid gap-8 px-6 py-10 lg:grid-cols-[1.3fr_0.7fr] lg:px-10">
            <div>
                <span class="inline-flex rounded-full bg-indigo-500/15 px-3 py-1 text-xs font-semibold text-indigo-300 ring-1 ring-inset ring-indigo-400/30">FYP01-SE-T2530-0794</span>
                <h2 class="mt-5 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-4xl">From manual coordination to optimized, conflict-free scheduling.</h2>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">The current phase provides the scheduling foundation. Authentication, role-specific workflows, event registration, attendance, analytics, and Genetic Algorithm optimization will follow incrementally.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-300">Open dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-300">Sign in</a>
                        <a href="{{ route('register') }}" class="rounded-lg border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-700">Create student account</a>
                    @endauth
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 self-end">
                @foreach ([['Events', 'Define requirements'], ['Venues', 'Manage capacity'], ['Timeslots', 'Set availability'], ['Schedules', 'Prevent conflicts']] as [$label, $detail])
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                        <p class="font-semibold text-white">{{ $label }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $detail }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-5 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Secure foundation</span>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Role-based access</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Students, organizers, and administrators receive separate dashboards and server-enforced permissions.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Next phase</span>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Event proposal workflow</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Add organizer ownership, event lifecycle states, venue requests, blackout dates, and administrator approvals.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-purple-600">Project goal</span>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">GA optimization</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Generate and compare candidate schedules using hard constraints, preferences, and measurable fitness scores.</p>
        </div>
    </section>
</x-layouts.app>
