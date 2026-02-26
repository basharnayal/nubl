<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-navy-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="card p-8 text-center">
        <h1 class="text-4xl font-bold text-slate-800 dark:text-navy-100 mb-4">Welcome! 👋</h1>
        <p class="text-xl text-slate-600 dark:text-navy-200 mb-6">You are logged in as: <strong>{{ auth()->user()->name }}</strong></p>

        @if(auth()->user()->roles->count() > 0)
            <div class="alert flex rounded-lg bg-warning/10 px-4 py-4 text-warning dark:bg-warning/15 dark:text-warning sm:px-5 mb-4">
                ⚠️ You have roles but RedirectByRole didn't redirect you. Check your roles:
                <ul class="mt-2">
                    @foreach(auth()->user()->roles as $role)
                        <li>- {{ $role->name }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="alert flex rounded-lg bg-error/10 px-4 py-4 text-error dark:bg-error/15 dark:text-error sm:px-5 mb-4">
                ⚠️ You don't have any role assigned. Please assign a role to test the redirect.
            </div>
        @endif

        <p class="text-slate-500 dark:text-navy-300">This is the default Dashboard</p>
    </div>
</x-app-layout>
