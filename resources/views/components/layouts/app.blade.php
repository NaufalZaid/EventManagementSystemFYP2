<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Event Management System') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php
        $navigation = [['label' => 'Overview', 'route' => 'home', 'pattern' => 'home']];

        if (auth()->check()) {
            $navigation = [['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard']];

            if (auth()->user()->hasRole('student')) {
                $navigation[] = ['label' => 'Discover Events', 'route' => 'discover.index', 'pattern' => 'discover.*'];
                $navigation[] = ['label' => 'My Events', 'route' => 'my-events.index', 'pattern' => 'my-events.*'];
                $navigation[] = ['label' => 'Calendar', 'route' => 'calendar.index', 'pattern' => 'calendar.*'];
                $navigation[] = ['label' => 'Notifications'.(auth()->user()->unreadNotifications()->count() ? ' ('.auth()->user()->unreadNotifications()->count().')' : ''), 'route' => 'notifications.index', 'pattern' => 'notifications.*'];
                $navigation[] = ['label' => 'Attendance History', 'route' => 'attendance.history', 'pattern' => 'attendance.history'];
            }

            if (auth()->user()->hasRole('organizer', 'administrator')) {
                $navigation[] = ['label' => 'Events', 'route' => 'events.index', 'pattern' => 'events.*'];
                $navigation[] = ['label' => 'Venue Requests', 'route' => 'venue-requests.index', 'pattern' => 'venue-requests.*'];
                $navigation[] = ['label' => 'Analytics', 'route' => 'analytics.index', 'pattern' => 'analytics.*'];
                $navigation[] = ['label' => 'Reports', 'route' => 'reports.index', 'pattern' => 'reports.*'];
            }

            if (auth()->user()->hasRole('administrator')) {
                $navigation[] = ['label' => 'Proposals', 'route' => 'proposals.index', 'pattern' => 'proposals.*'];
                $navigation[] = ['label' => 'Venues', 'route' => 'venues.index', 'pattern' => 'venues.*'];
                $navigation[] = ['label' => 'Timeslots', 'route' => 'timeslots.index', 'pattern' => 'timeslots.*'];
                $navigation[] = ['label' => 'Schedules', 'route' => 'schedules.index', 'pattern' => 'schedules.*'];
                $navigation[] = ['label' => 'GA Optimizer', 'route' => 'optimizer.index', 'pattern' => 'optimizer.*'];
                $navigation[] = ['label' => 'GA Experiments', 'route' => 'experiments.index', 'pattern' => 'experiments.*'];
                $navigation[] = ['label' => 'Evaluation Results', 'route' => 'evaluation.results', 'pattern' => 'evaluation.results'];
            }

            $navigation[] = ['label' => 'Evaluate System', 'route' => 'evaluation.edit', 'pattern' => 'evaluation.edit'];
        }

        $brandRoute = auth()->check() ? route('dashboard') : route('home');
    @endphp

    <nav class="fixed top-0 z-50 w-full border-b border-slate-200 bg-white">
        <div class="px-3 py-3 lg:px-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button data-drawer-target="app-sidebar" data-drawer-toggle="app-sidebar" aria-controls="app-sidebar" type="button" class="inline-flex rounded-lg p-2 text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:hidden">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <a href="{{ $brandRoute }}" class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">EMS</span>
                        <span class="hidden sm:block"><span class="block text-sm font-semibold text-slate-900">Event Management System</span><span class="block text-xs text-slate-500">MMU scheduling prototype</span></span>
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200 sm:inline-flex">Phase 8 · Evaluation evidence</span>
                    @auth
                        @php($initials = collect(explode(' ', auth()->user()->name))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join(''))
                        <button id="user-menu-button" data-dropdown-toggle="user-menu" type="button" class="grid h-9 w-9 place-items-center rounded-full bg-slate-900 text-xs font-semibold text-white" aria-expanded="false">{{ $initials }}</button>
                        <div id="user-menu" class="z-50 hidden w-60 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white text-sm shadow-lg">
                            <div class="px-4 py-3"><p class="font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p><span class="mt-2 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ auth()->user()->role->label() }}</span></div>
                            <form method="POST" action="{{ route('logout') }}" class="p-2">@csrf<button type="submit" class="w-full rounded-lg px-3 py-2 text-left font-medium text-red-600 hover:bg-red-50">Sign out</button></form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-indigo-600">Sign in</a>
                        <a href="{{ route('register') }}" class="hidden rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 sm:inline-flex">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <aside id="app-sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 -translate-x-full border-r border-slate-200 bg-slate-950 pt-16 transition-transform sm:translate-x-0" aria-label="Sidebar">
        <div class="flex h-full flex-col overflow-y-auto px-3 py-5">
            <div class="mb-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Workspace</div>
            <ul class="space-y-1 font-medium">
                @foreach ($navigation as $item)
                    @php($active = request()->routeIs($item['pattern']) || ($item['route'] === 'calendar.index' && request()->routeIs('commitments.*')))
                    <li><a href="{{ route($item['route']) }}" @class(['group flex items-center rounded-lg px-3 py-2.5 text-sm transition', 'bg-indigo-600 text-white shadow-sm' => $active, 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $active])><span @class(['mr-3 h-2 w-2 rounded-full', 'bg-white' => $active, 'bg-slate-600 group-hover:bg-indigo-400' => ! $active])></span>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            @auth
                <div class="mt-auto rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs font-semibold text-indigo-300">Signed in as</p><p class="mt-1 text-sm font-medium text-white">{{ auth()->user()->role->label() }}</p><p class="mt-1 text-xs leading-5 text-slate-400">Navigation is limited to the functions authorized for this role.</p></div>
            @else
                <div class="mt-auto rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs font-semibold text-indigo-300">Role-based EMS</p><p class="mt-1 text-xs leading-5 text-slate-400">Sign in to access your student, organizer, or administrator workspace.</p><a href="{{ route('login') }}" class="mt-3 inline-flex text-xs font-semibold text-white hover:text-indigo-300">Sign in →</a></div>
            @endauth
        </div>
    </aside>

    <main class="min-h-screen pt-16 sm:ml-64"><div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8"><x-flash-message />{{ $slot }}</div></main>
</body>
</html>
