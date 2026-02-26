<x-guest-layout max-width="wide" :title="null">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-navy-100">
            {{ __('Welcome to') }} {{ config('app.name') }}
        </h1>
        <p class="text-slate-600 dark:text-navy-300">
            {{ __('A platform connecting donors, providers, and recipients.') }}
        </p>

        @if (Route::has('login'))
            <div class="flex flex-wrap gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                        {{ __('Log in') }}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="btn border border-slate-300 text-slate-700 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
                            {{ __('Register') }}
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</x-guest-layout>
