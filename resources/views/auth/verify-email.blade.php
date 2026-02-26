<x-guest-layout :title="__('Verify Email')">
    <h1 class="text-xl font-semibold text-slate-800 dark:text-navy-100 mb-2">{{ __('Verify your email') }}</h1>
    <div class="mb-4 text-sm text-slate-600 dark:text-navy-300">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-primary dark:text-accent-light">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-primary dark:text-accent-light hover:text-primary-focus dark:hover:text-accent font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:focus:ring-accent">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
