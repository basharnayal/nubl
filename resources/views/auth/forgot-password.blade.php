<x-login-layout :title="__('Forgot Password')" :heading="__('Forgot password?')" :subheading="__('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')">
    <div class="w-full space-y-4">
        <x-auth-session-status class="mb-0" :status="session('status')" />

        @if ($errors->has('email'))
            <div class="p-3 rounded-lg bg-error/10 dark:bg-error/15 border border-error/20" role="alert">
                <p class="text-sm text-error dark:text-error">{{ $errors->first('email') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <label class="relative flex">
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" dir="ltr"
                    class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 text-left ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring-3 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900 @error('email') border-error dark:border-error @enderror"
                    placeholder="{{ __('Email') }}" />
                <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
            </label>
            <x-input-error :messages="$errors->get('email')" class="mt-1" />

            <button type="submit"
                class="btn mt-6 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                {{ __('Email Password Reset Link') }}
            </button>
        </form>

        <div class="mt-4 text-center text-xs-plus">
            <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="{{ route('login') }}">
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</x-login-layout>
