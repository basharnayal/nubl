@php
    $reason = session('payment_reason');
    $retryMessage = match ($reason) {
        'api_unavailable' => __('The payment service is temporarily unavailable. Please try again in a few moments.'),
        'ambiguous' => __('We received an unclear response from the payment service. Please try again.'),
        'missing_callback' => __('We could not confirm your payment because the gateway did not return a valid reference. Please try again or contact support.'),
        'processing_error' => __('Your payment may have been received but we could not finish processing it. Please contact support if the amount was charged.'),
        'payment_not_found' => __('We could not match this payment to your donation. If you were charged, please contact support with your bank or gateway receipt.'),
        'gateway_declined' => __('The payment was not completed or was declined by the payment provider.'),
        default => __('Your payment could not be processed. Please try again or contact support if the problem persists.'),
    };
@endphp
<x-guest-layout title="{{ __('Payment Failed') }}">
    <div class="text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <svg class="size-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
            {{ __('Payment was not completed') }}
        </h2>
        <p class="mt-4 text-sm text-slate-500 dark:text-navy-400">
            {{ $retryMessage }}
        </p>
    </div>

    <div class="mt-6 flex justify-center gap-4">
        <a href="{{ route('home') }}" class="btn bg-primary text-white hover:bg-primary-focus px-5 py-2 dark:bg-accent dark:hover:bg-accent-focus">
            {{ __('Try Again') }}
        </a>
        <a href="{{ route('home') }}" class="btn border-2 border-slate-300 bg-transparent text-slate-700 hover:bg-slate-100 px-5 py-2 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
            {{ __('Back to Home') }}
        </a>
    </div>
</x-guest-layout>
