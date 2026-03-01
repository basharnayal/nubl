<x-app-layout title="{{ __('Payment Failed') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-2xl">
                <div class="card p-8 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-error/10">
                        <i class="fa-solid fa-circle-xmark text-3xl text-error"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                        {{ __('Payment was not completed') }}
                    </h2>
                    <p class="mt-4 text-sm text-slate-500 dark:text-navy-400">
                        {{ __('Your payment could not be processed. Please try again or contact support if the problem persists.') }}
                    </p>
                    <div class="mt-6 flex justify-center gap-4">
                        <x-lineone-button :href="route('donor.donations.new')" variant="primary">{{ __('Try Again') }}</x-lineone-button>
                        <x-lineone-button :href="route('donor.dashboard')" variant="slate" outline>{{ __('Back to Dashboard') }}</x-lineone-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
