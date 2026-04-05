@php
    $reason = session('payment_reason');
    $retryMessage = match ($reason) {
        'api_unavailable' => __('The payment service is temporarily unavailable. Please try again in a few moments.'),
        'ambiguous' => __('We received an unclear response from the payment service. Please try again.'),
        'missing_callback' => __('We could not confirm your payment because the gateway did not return a valid reference. Please try again or contact support.'),
        'processing_error' => __('Your payment may have been received but we could not finish updating your account. Please check your donations list or contact support with your receipt if the amount was charged.'),
        'payment_not_found' => __('We could not match this payment to your donation. If you were charged, please contact support with your bank or gateway receipt.'),
        'gateway_declined' => __('The payment was not completed or was declined by the payment provider.'),
        default => __('Your payment could not be processed. Please try again or contact support if the problem persists.'),
    };
@endphp
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
                        {{ $retryMessage }}
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
