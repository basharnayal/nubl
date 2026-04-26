<x-app-layout title="{{ __('Payment Successful') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="mx-auto max-w-2xl">
                <div class="card overflow-hidden">
                    {{-- Success header --}}
                    <div class="border-b border-slate-200 bg-success/5 px-6 py-6 text-center dark:border-navy-600 dark:bg-success/10">
                        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-success/10">
                            <i class="fa-solid fa-circle-check text-3xl text-success"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                            {{ __('Thank you! Your donation was successful.') }}
                        </h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-navy-400">
                            {{ __('Your contribution has been added to the city fund and will reach those most in need.') }}
                        </p>
                    </div>

                    {{-- Receipt summary --}}
                    @if ($payment)
                        <div class="border-b border-slate-200 px-6 py-5 dark:border-navy-600">
                            <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">{{ __('Donation Receipt') }}</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-slate-500 dark:text-navy-400">{{ __('Payment Status') }}</span>
                                    <span class="font-semibold text-success">{{ __('Succeeded') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500 dark:text-navy-400">{{ __('Donor') }}</span>
                                    <span class="font-semibold text-slate-700 dark:text-navy-100">{{ $payment->sponsor?->name ?? __('Donor') }}</span>
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

                    {{-- Actions --}}
                    <div class="flex flex-wrap justify-center gap-3 px-6 py-6">
                        @if ($payment)
                            <x-lineone-button :href="route('donor.donations.receipt', $payment)" variant="primary">
                                <i class="fa-solid fa-receipt mr-2"></i>
                                {{ __('View Full Receipt') }}
                            </x-lineone-button>
                        @endif
                        <x-lineone-button :href="route('donor.donations.index')" variant="slate" outline>{{ __('My Donations') }}</x-lineone-button>
                        <x-lineone-button :href="route('donor.dashboard')" variant="slate" outline>{{ __('Back to Dashboard') }}</x-lineone-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
