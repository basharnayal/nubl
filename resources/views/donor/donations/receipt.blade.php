<x-app-layout title="{{ __('Donation Receipt') }} #{{ $payment->id }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mx-auto max-w-lg">
            {{-- Receipt Card --}}
            <div class="card overflow-hidden print:shadow-none" id="receipt">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-navy-600 dark:bg-navy-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-navy-100">
                            {{ __('Donation Receipt') }}
                        </h2>
                        <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-medium text-success">
                            {{ __('Completed') }}
                        </span>
                    </div>
                </div>
                <div class="space-y-4 px-6 py-6">
                    <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                        <span class="text-slate-500 dark:text-navy-400">{{ __('Receipt No.') }}</span>
                        <span class="font-mono font-medium text-slate-700 dark:text-navy-100">#{{ $payment->id }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                        <span class="text-slate-500 dark:text-navy-400">{{ __('Date') }}</span>
                        <span class="font-medium text-slate-700 dark:text-navy-100">{{ $payment->created_at->translatedFormat('F j, Y \a\t H:i') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                        <span class="text-slate-500 dark:text-navy-400">{{ __('Amount') }}</span>
                        <span class="text-xl font-bold text-primary dark:text-accent-light"><x-sar-symbol /> {{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-150 pb-3 dark:border-navy-600">
                        <span class="text-slate-500 dark:text-navy-400">{{ __('Donor') }}</span>
                        <span class="font-medium text-slate-700 dark:text-navy-100">{{ $payment->sponsor?->name ?? __('Donor') }}</span>
                    </div>
                    @if ($payment->requestPaymentLinks->isNotEmpty())
                        <div class="rounded-lg bg-slate-100 px-4 py-3 dark:bg-navy-600">
                            <p class="text-sm font-medium text-slate-600 dark:text-navy-300">
                                {{ __('Your contribution has been allocated to') }} {{ $payment->requestPaymentLinks->count() }} {{ __('request(s)') }}
                            </p>
                        </div>
                    @endif
                    <p class="text-center text-xs text-slate-400 dark:text-navy-500">
                        {{ __('Thank you for your support. Your contribution helps those in need.') }}
                    </p>
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-navy-600 dark:bg-navy-800 print:border-t">
                    <p class="text-center text-xs text-slate-500 dark:text-navy-400">
                        {{ config('app.name') }} — {{ __('City Fund Donation') }}
                    </p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex flex-wrap justify-center gap-3 print:hidden">
                <button onclick="window.print()" class="btn inline-flex items-center gap-2 bg-primary px-5 py-2.5 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                    <i class="fa-solid fa-print"></i>
                    <span>{{ __('Print Receipt') }}</span>
                </button>
                <x-lineone-button :href="route('donor.donations.index')" variant="slate" outline>
                    {{ __('Back to My Donations') }}
                </x-lineone-button>
                <x-lineone-button :href="route('donor.dashboard')" variant="slate" outline>
                    {{ __('Dashboard') }}
                </x-lineone-button>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .sidebar, nav.header, .print\:hidden { display: none !important; }
            .main-content { padding: 1rem !important; }
        }
    </style>
</x-app-layout>
