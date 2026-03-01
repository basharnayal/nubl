<x-app-layout title="{{ __('Payment Successful') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-2xl">
                <div class="card p-8 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-success/10">
                        <i class="fa-solid fa-circle-check text-3xl text-success"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                        {{ __('Thank you! Your donation was successful.') }}
                    </h2>
                    @if ($payment)
                        <p class="mt-2 text-slate-600 dark:text-navy-300">
                            {{ __('Amount') }}: {{ number_format($payment->amount, 2) }} {{ __('SAR') }}
                        </p>
                    @endif
                    <p class="mt-4 text-sm text-slate-500 dark:text-navy-400">
                        {{ __('Your contribution has been added to the city fund and will help those in need.') }}
                    </p>
                    <div class="mt-6">
                        <x-lineone-button :href="route('donor.dashboard')" variant="primary">{{ __('Back to Dashboard') }}</x-lineone-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
