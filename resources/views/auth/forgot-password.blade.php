<x-layouts.guest title="Forgot password">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Reset your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Enter your account email and we will send a secure password-reset link.</p>
        <x-form-errors />
        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">@csrf<div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div><button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Email reset link</button></form>
        <p class="mt-6 text-center"><a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to sign in</a></p>
    </div>
</x-layouts.guest>
