<x-layouts.guest title="Sign in">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h1>
        <p class="mt-2 text-sm text-slate-600">Sign in to access your role-specific workspace.</p>
        <x-form-errors />
        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
            @csrf
            <div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="you@example.com"></div>
            <div><div class="mb-2 flex items-center justify-between"><label for="password" class="text-sm font-medium text-slate-700">Password</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Forgot password?</a></div><input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">Remember me</label>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">Sign in</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-600">New student? <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Create an account</a></p>
    </div>
</x-layouts.guest>
