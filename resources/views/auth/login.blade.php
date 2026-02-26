<x-guest-layout>
    @php
        $otpPhone = session('otp_phone') ?? old('otp_phone');
        $hasOtpErrors = $errors->has('otp_phone') || $errors->has('otp_code');
        $hasEmailErrors = $errors->has('email') || $errors->has('password');
        $defaultTab = ($hasEmailErrors && !$hasOtpErrors && !$otpPhone) ? 'email' : 'otp';
    @endphp

    <div x-data="{ tab: '{{ $defaultTab }}' }" class="w-full">
        {{-- Toggle: Phone (OTP) | Email & Password --}}
        <div role="tablist" aria-label="{{ __('Login method') }}" class="flex rounded-xl bg-slate-100 dark:bg-navy-600 p-1 mb-6">
            <button type="button" role="tab" :aria-selected="tab === 'otp'" aria-controls="panel-otp" id="tab-otp"
                @click="tab = 'otp'"
                :class="tab === 'otp' ? 'bg-white dark:bg-navy-700 text-primary dark:text-accent-light shadow-sm' : 'text-slate-600 dark:text-navy-300 hover:text-slate-800 dark:hover:text-navy-100'"
                class="flex-1 py-2.5 px-4 text-sm font-medium rounded-lg transition-all duration-200 ease-out">
                {{ __('Phone (OTP)') }}
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'email'" aria-controls="panel-email" id="tab-email"
                @click="tab = 'email'"
                :class="tab === 'email' ? 'bg-white dark:bg-navy-700 text-primary dark:text-accent-light shadow-sm' : 'text-slate-600 dark:text-navy-300 hover:text-slate-800 dark:hover:text-navy-100'"
                class="flex-1 py-2.5 px-4 text-sm font-medium rounded-lg transition-all duration-200 ease-out">
                {{ __('Email & Password') }}
            </button>
        </div>

        {{-- OTP Login (default) --}}
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
                    <p class="mb-3 text-sm text-slate-600">
                        {{ __('Code sent to') }}: +966 *** *** {{ substr($otpPhone, -4) }}
                    </p>
                    <div>
                        <x-input-label for="otp_code" :value="__('Verification code')" required />
                        <x-text-input
                            id="otp_code"
                            name="otp_code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="block mt-1 w-full text-center text-lg tracking-[0.5em]"
                            placeholder="000000"
                            autofocus
                            :value="old('otp_code')"
                        />
                    </div>
                    <div class="block mt-4">
                        <label for="otp_remember" class="inline-flex items-center">
                            <input id="otp_remember" type="checkbox" class="rounded border-gray-300 text-primary dark:text-accent-light shadow-sm focus:ring-primary dark:focus:ring-accent" name="otp_remember">
                            <span class="ms-2 text-sm text-slate-600 dark:text-navy-300">{{ __('Remember me') }}</span>
                        </label>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                        <x-primary-button type="submit" class="w-full sm:w-auto">
                            {{ __('Verify and log in') }}
                        </x-primary-button>
                        <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-700 underline text-center sm:text-left">
                            {{ __('Use different phone') }}
                        </a>
                    </div>
                </form>
            @else
                {{-- Step 1: Enter phone, request OTP --}}
                <form method="POST" action="{{ route('login.otp.request') }}">
                    @csrf
                    <div>
                        <x-input-label for="otp_phone_input" :value="__('Phone number')" required />
                        <x-text-input
                            id="otp_phone_input"
                            name="phone"
                            type="tel"
                            class="block mt-1 w-full"
                            placeholder="05XXXXXXXX"
                            :value="old('phone')"
                            autocomplete="tel"
                        />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Enter your Saudi phone number (e.g. 05XXXXXXXX)') }}</p>
                    </div>
                    <div class="mt-4">
                        <x-primary-button type="submit" class="w-full sm:w-auto">
                            {{ __('Send verification code') }}
                    </x-primary-button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Email + Password Login --}}
        <div id="panel-email" role="tabpanel" aria-labelledby="tab-email" x-show="tab === 'email'" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="space-y-4">

            <x-auth-session-status class="mb-0" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" required />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" required />
                    <x-text-input id="password" class="block mt-1 w-full"
                                    type="password"
                                    name="password"
                                    required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary dark:text-accent-light shadow-sm focus:ring-primary dark:focus:ring-accent" name="remember">
                        <span class="ms-2 text-sm text-slate-600 dark:text-navy-300">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4 gap-3">
                    @if (Route::has('password.request'))
                        <a class="underline text-sm text-primary dark:text-accent-light hover:text-primary-focus dark:hover:text-accent font-medium rounded-md" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                    <x-primary-button>
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
