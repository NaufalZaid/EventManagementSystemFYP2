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
<body class="min-h-screen bg-slate-950">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden bg-slate-950 p-12 lg:flex lg:flex-col lg:justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-white"><span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-sm font-bold">EMS</span><span class="font-semibold">Event Management System</span></a>
            <div class="max-w-xl"><span class="rounded-full bg-indigo-500/15 px-3 py-1 text-xs font-semibold text-indigo-300 ring-1 ring-indigo-400/30">Multimedia University</span><h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-white">University events, coordinated in one place.</h1><p class="mt-5 text-sm leading-7 text-slate-300">Access event registration, organizer planning, administrative approvals, venue scheduling, attendance, and reporting through your assigned workspace.</p></div>
            <p class="text-xs text-slate-500">Event Management System · Multimedia University</p>
        </section>
        <section class="flex items-center justify-center bg-slate-50 px-5 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3 text-slate-900 lg:hidden"><span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white">EMS</span><span class="font-semibold">Event Management System</span></a>
                @if (session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>@endif
                {{ $slot }}
            </div>
        </section>
    </main>
</body>
</html>
