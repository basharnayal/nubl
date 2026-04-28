<x-guest-layout title="{{ __('Donation Successful') }}">
    <div class="text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <svg class="size-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
            {{ __('Thank you! Your donation was successful.') }}
        </h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-navy-400">
            {{ __('Your contribution has been added to the city fund and will reach those most in need.') }}
        </p>
    </div>

    @if ($payment)
        <div class="mt-6 border-t border-slate-200 pt-5 dark:border-navy-600">
            <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">{{ __('Donation Receipt') }}</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Payment Status') }}</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ __('Succeeded') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Donor') }}</span>
                    <span class="font-semibold text-slate-700 dark:text-navy-100">{{ __('Guest Donor') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Donation No.') }}</span>
                    <span class="font-mono text-slate-700 dark:text-navy-100">DON-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Payment Gateway Reference') }}</span>
                    <span class="font-mono text-slate-700 dark:text-navy-100">{{ $payment->external_payment_id ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Amount') }}</span>
                    <span class="font-semibold text-slate-700 dark:text-navy-100"><x-sar-amount :value="number_format($payment->amount, 2)" /></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-navy-400">{{ __('Donation Path') }}</span>
                    <span class="text-slate-700 dark:text-navy-100">{{ __('Madinah City Fund') }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        @if ($payment)
            <a href="{{ route('guest.donation.receipt', ['token' => $payment->idempotency_key]) }}" class="btn inline-flex items-center gap-2 bg-primary text-white hover:bg-primary-focus px-5 py-2 dark:bg-accent dark:hover:bg-accent-focus">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                {{ __('View Full Receipt') }}
            </a>
        @endif
        <a href="{{ route('home') }}" class="btn border-2 border-slate-300 bg-transparent text-slate-700 hover:bg-slate-100 px-5 py-2 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
            {{ __('Back to Home') }}
        </a>
    </div>
</x-guest-layout>
