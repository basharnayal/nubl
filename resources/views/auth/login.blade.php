<x-login-layout :title="__('Login')">
    @php
        $otpPhone = session('otp_phone') ?? old('otp_phone');
        $hasOtpErrors = $errors->has('otp_phone') || $errors->has('otp_code');
        $hasEmailErrors = $errors->has('email') || $errors->has('password');
        $defaultTab = ($otpPhone || ($hasOtpErrors && !$hasEmailErrors)) ? 'otp' : 'email';
    @endphp

    <div x-data="{ tab: '{{ $defaultTab }}' }" class="w-full">
        {{-- Toggle: Email & Password | Phone (OTP) – Email is default --}}
        <div role="tablist" aria-label="{{ __('Login method') }}" class="flex rounded-xl bg-slate-100 dark:bg-navy-600 p-1 mb-6">
            <button type="button" role="tab" :aria-selected="tab === 'email'" aria-controls="panel-email" id="tab-email"
                @click="tab = 'email'"
                :class="tab === 'email' ? 'bg-white dark:bg-navy-700 text-primary dark:text-accent-light shadow-sm' : 'text-slate-600 dark:text-navy-300 hover:text-slate-800 dark:hover:text-navy-100'"
                class="flex-1 py-2.5 px-4 text-sm font-medium rounded-lg transition-all duration-200 ease-out">
                {{ __('Email & Password') }}
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'otp'" aria-controls="panel-otp" id="tab-otp"
                @click="tab = 'otp'"
                :class="tab === 'otp' ? 'bg-white dark:bg-navy-700 text-primary dark:text-accent-light shadow-sm' : 'text-slate-600 dark:text-navy-300 hover:text-slate-800 dark:hover:text-navy-100'"
                class="flex-1 py-2.5 px-4 text-sm font-medium rounded-lg transition-all duration-200 ease-out">
                {{ __('Phone (OTP)') }}
            </button>
        </div>

        {{-- Email + Password Login (default) --}}
        <div id="panel-email" role="tabpanel" aria-labelledby="tab-email" x-show="tab === 'email'" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="space-y-4">

            <x-auth-session-status class="mb-0" :status="session('status')" />

            @if ($hasEmailErrors)
                <div class="p-3 rounded-lg bg-error/10 dark:bg-error/15 border border-error/20" role="alert">
                    <p class="text-sm text-error dark:text-error">{{ $errors->first('email') ?: $errors->first('password') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label class="relative flex">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" dir="ltr"
                        class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 text-left ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring-3 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900 @error('email') border-error dark:border-error @enderror"
                        placeholder="{{ __('Email') }}" />
                    <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('email')" class="mt-1" />

                <label class="relative mt-4 flex">
                    <input id="password" name="password" type="password" required autocomplete="current-password" dir="ltr"
                        class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 text-left ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring-3 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900 @error('password') border-error dark:border-error @enderror"
                        placeholder="{{ __('Password') }}" />
                    <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />

                <div class="mt-4 flex items-center justify-between space-x-2">
                    <label class="inline-flex items-center space-x-2">
                        <input id="remember_me" name="remember" type="checkbox"
                            class="form-checkbox is-outline size-5 rounded-sm border-slate-400/70 bg-slate-100 before:bg-primary checked:border-primary hover:border-primary focus:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent dark:hover:border-accent dark:focus:border-accent" />
                        <span class="line-clamp-1 text-sm text-slate-600 dark:text-navy-300">{{ __('Remember me') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="text-xs text-slate-400 transition-colors line-clamp-1 hover:text-slate-800 focus:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100 dark:focus:text-navy-100">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>

                <button type="submit"
                    class="btn mt-10 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    {{ __('Log in') }}
                </button>
            </form>

            @if (Route::has('register'))
                <div class="mt-4 text-center text-xs-plus">
                    <p class="line-clamp-1">
                        <span>{{ __('Dont have Account?') }}</span>
                        <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="{{ route('register') }}">
                            {{ __('Create account') }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        {{-- OTP Login --}}
        <div id="panel-otp" role="tabpanel" aria-labelledby="tab-otp" x-show="tab === 'otp'" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="space-y-4">

            @if (session('otp_status'))
                <div class="font-medium text-sm text-primary dark:text-accent-light">
                    {{ session('otp_status') }}
                </div>
            @endif

            @if ($hasOtpErrors)
                <div class="p-3 rounded-lg bg-error/10 dark:bg-error/15 border border-error/20" role="alert">
                    <p class="text-sm text-error dark:text-error">{{ $errors->first('otp_phone') ?: $errors->first('otp_code') }}</p>
                </div>
            @endif

            @if ($otpPhone)
                {{-- Step 2: Enter OTP --}}
                <form method="POST" action="{{ route('login.otp.verify') }}">
                    @csrf
                    <input type="hidden" name="otp_phone" value="{{ $otpPhone }}">
                    <p class="mb-3 text-sm text-slate-600 dark:text-navy-300">
                        {{ __('Code sent to') }}: +966 *** *** {{ substr($otpPhone, -4) }}
                    </p>
                    <label class="relative flex">
                        <input id="otp_code" name="otp_code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            autocomplete="one-time-code" value="{{ old('otp_code') }}" dir="ltr"
                            class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 text-left ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring-3 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900"
                            placeholder="{{ __('Verification code') }}" autofocus />
                        <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                        </span>
                    </label>
                    <div class="block mt-4">
                        <label for="otp_remember" class="inline-flex items-center space-x-2">
                            <input id="otp_remember" type="checkbox" name="otp_remember"
                                class="form-checkbox is-outline size-5 rounded-sm border-slate-400/70 bg-slate-100 before:bg-primary checked:border-primary hover:border-primary focus:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent dark:hover:border-accent dark:focus:border-accent" />
                            <span class="text-sm text-slate-600 dark:text-navy-300">{{ __('Remember me') }}</span>
                        </label>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                        <button type="submit"
                            class="btn h-10 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 w-full sm:w-auto">
                            {{ __('Verify and log in') }}
                        </button>
                        <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-navy-200 underline text-center sm:text-left">
                            {{ __('Use different phone') }}
                        </a>
                    </div>
                </form>
            @else
                {{-- Step 1: Enter phone, request OTP --}}
                <form method="POST" action="{{ route('login.otp.request') }}">
                    @csrf
                    <label class="relative flex">
                        <input id="otp_phone_input" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"
                            required maxlength="10" minlength="9" pattern="[0-9]{9,10}" inputmode="numeric" dir="ltr"
                            class="form-input peer w-full rounded-lg bg-slate-150 px-3 py-2 pl-9 text-left ring-primary/50 placeholder:text-slate-400 hover:bg-slate-200 focus:ring-3 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 dark:hover:bg-navy-900 dark:focus:bg-navy-900"
                            placeholder="{{ __('Phone placeholder') }}" />
                        <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('Enter your Saudi phone number (e.g. 05XXXXXXXX)') }}</p>
                    <button type="submit"
                        class="btn mt-4 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                        {{ __('Send verification code') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-login-layout>
