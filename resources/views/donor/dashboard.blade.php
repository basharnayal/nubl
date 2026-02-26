<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-navy-100 leading-tight">
            {{ __('Donor Dashboard') }}
        </h2>
    </x-slot>

    <div class="card p-8 text-center">
        <h1 class="text-4xl font-bold text-slate-800 dark:text-navy-100 mb-4">Welcome Donor! 💝</h1>
        <p class="text-xl text-slate-600 dark:text-navy-200 mb-6">You are logged in as: <strong>Donor</strong></p>
        <div class="alert flex rounded-lg bg-success/10 px-4 py-4 text-success dark:bg-success/15 dark:text-success sm:px-5 mb-4">
            ✅ RedirectByRole Middleware is working correctly!
        </div>
        <p class="text-slate-500 dark:text-navy-300">This is the Donor Dashboard</p>
    </div>
</x-app-layout>
