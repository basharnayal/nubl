<x-guest-layout title="{{ __('Donation Receipt') }} #{{ $payment->id }}">
    {{-- Receipt Card --}}
    <div id="receipt">
        <div class="border-b border-slate-200 pb-4 dark:border-navy-600">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800 dark:text-navy-100">
                    {{ __('Donation Receipt') }}
                </h2>
                <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    {{ __('Completed') }}
                </span>
            </div>
        </div>

        <div class="mt-5 space-y-4 text-sm">
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Receipt No.') }}</span>
                <span class="font-mono font-medium text-slate-700 dark:text-navy-100">#{{ $payment->id }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Date') }}</span>
                <span class="font-medium text-slate-700 dark:text-navy-100">{{ $payment->created_at->translatedFormat('F j, Y \a\t H:i') }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Donation No.') }}</span>
                <span class="font-mono font-medium text-slate-700 dark:text-navy-100">DON-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Payment Gateway Reference') }}</span>
                <span class="font-mono font-medium text-slate-700 dark:text-navy-100">{{ $payment->external_payment_id ?? '—' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Amount') }}</span>
                <span class="text-xl font-bold text-slate-800 dark:text-navy-50"><x-sar-amount :value="number_format($payment->amount, 2)" /></span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Donor') }}</span>
                <span class="font-medium text-slate-700 dark:text-navy-100">{{ __('Guest Donor') }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                <span class="text-slate-500 dark:text-navy-400">{{ __('Donation Path') }}</span>
                <span class="font-medium text-slate-700 dark:text-navy-100">{{ __('Madinah City Fund') }}</span>
            </div>
            <p class="pt-2 text-center text-xs text-slate-400 dark:text-navy-500">
                {{ __('Thank you for your support. Your contribution helps those in need.') }}
            </p>
        </div>

        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-navy-600">
            <p class="text-center text-xs text-slate-500 dark:text-navy-400">
                {{ config('app.name') }} — {{ __('City Fund Donation') }}
            </p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 flex flex-wrap justify-center gap-3 print:hidden">
        <button onclick="window.print()" class="btn inline-flex items-center gap-2 bg-primary text-white hover:bg-primary-focus px-5 py-2 dark:bg-accent dark:hover:bg-accent-focus">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M9.75 8.25h.008v.008H9.75V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
            {{ __('Print Receipt') }}
        </button>
        <a href="{{ route('home') }}" class="btn border-2 border-slate-300 bg-transparent text-slate-700 hover:bg-slate-100 px-5 py-2 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
            {{ __('Back to Home') }}
        </a>
    </div>

    <style>
        @media print {
            .print\:hidden { display: none !important; }
            body { background: white !important; }
        }
    </style>
</x-guest-layout>
