<x-layouts.guest title="Register">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create student account</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Public registration creates a student account. Organizer and administrator access must be assigned by an authorized administrator.</p>
        <x-form-errors />
        <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
            @csrf
            <div><label for="name" class="mb-2 block text-sm font-medium text-slate-700">Full name</label><input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" autofocus required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <div><label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label><input id="password" name="password" type="password" autocomplete="new-password" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <div><label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="block w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">Create student account</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Sign in</a></p>
    </div>
</x-layouts.guest>
