<x-guest-layout :title="__('Verify Phone')">
    <div class="w-full">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ __('Verify your phone number') }}</h1>
        <p class="mb-4 text-sm text-slate-600">
            {{ __('We sent a 6-digit verification code to your phone. Enter it below.') }}
        </p>

        @php
            $phone = auth()->user()?->phone_number ?? auth()->user()?->providerProfile?->phone_number ?? '';
            $masked = $phone ? ('+966 *** *** ' . substr($phone, -4)) : '';
        @endphp
        @if($masked)
            <p class="mb-4 text-sm text-slate-500">
                {{ __('Sent to') }}: {{ $masked }}
            </p>
        @endif

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-primary dark:text-accent-light">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-error/10 dark:bg-error/15 border border-error/20" role="alert">
                <p class="text-sm text-error">{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('verification.phone.verify') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="otp" :value="__('Verification code')" required />
                <x-text-input
                    id="otp"
                    name="otp"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="block mt-1 w-full text-center text-lg tracking-[0.5em]"
                    placeholder="{{ __('Verification code placeholder') }}"
                    autofocus
                    :value="old('otp')"
                />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-between items-stretch sm:items-center">
                <x-primary-button type="submit" class="w-full sm:w-auto">
                    {{ __('Verify') }}
                </x-primary-button>
            </div>
        </form>

        <div x-data="resendOtp()" x-init="init()" class="mt-4">
            <form method="POST" action="{{ route('verification.phone.resend') }}" :class="{ 'pointer-events-none': countdown > 0 }">
                @csrf
                <button type="submit"
                    :disabled="countdown > 0"
                    class="btn w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-navy-500 text-slate-700 dark:text-navy-200 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-navy-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="countdown > 0" x-cloak x-text="'{{ __('Resend in') }} ' + countdown + 's'"></span>
                    <span x-show="countdown === 0" x-cloak>{{ __('Resend code') }}</span>
                </button>
            </form>
        </div>

        <script>
            function resendOtp() {
                return {
                    countdown: 30,
                    interval: null,
                    init() {
                        const saved = sessionStorage.getItem('otp_resend_at');
                        if (saved) {
                            const remaining = Math.ceil((parseInt(saved) - Date.now()) / 1000);
                            this.countdown = Math.max(0, remaining);
                        }
                        this.startTimer();
                    },
                    startTimer() {
                        if (this.countdown <= 0) return;
                        this.interval = setInterval(() => {
                            this.countdown--;
                            if (this.countdown <= 0) {
                                clearInterval(this.interval);
                                sessionStorage.removeItem('otp_resend_at');
                            }
                        }, 1000);
                    }
                };
            }
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form && form.action && form.action.includes('verify-phone/resend')) {
                    sessionStorage.setItem('otp_resend_at', Date.now() + 30000);
                }
            });
        </script>

        <div class="mt-6 pt-4 border-t border-slate-200 dark:border-navy-600">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-slate-500 dark:text-navy-400 hover:text-slate-700 dark:hover:text-navy-200 underline">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
