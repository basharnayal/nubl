<x-app-layout title="{{ __('My Donations') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="col-span-12">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('My Donations & Impact') }}
                </h2>

                <div class="card mt-3 p-6">
                    @forelse ($payments as $payment)
                        <div class="border-b border-slate-200 py-4 last:border-0 dark:border-navy-600">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium text-slate-700 dark:text-navy-100">
                                        {{ number_format($payment->amount, 2) }} {{ __('SAR') }}
                                    </p>
                                    <p class="text-sm text-slate-500 dark:text-navy-400">
                                        {{ $payment->created_at->translatedFormat('M d, Y H:i') }}
                                    </p>
                                    @if ($payment->requestPaymentLinks->isNotEmpty())
                                        <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                                            {{ __('Contributed to') }} {{ $payment->requestPaymentLinks->count() }} {{ __('request(s)') }}
                                        </p>
                                    @endif
                                </div>
                                <a href="{{ route('donor.donations.receipt', $payment) }}" class="btn inline-flex shrink-0 items-center gap-2 border border-primary bg-transparent px-4 py-2 text-primary hover:bg-primary/10 dark:border-accent dark:text-accent dark:hover:bg-accent/10">
                                    <i class="fa-solid fa-receipt"></i>
                                    <span>{{ __('View Receipt') }}</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-slate-500 dark:text-navy-400">
                            {{ __('No donations yet.') }}
                            <a href="{{ route('donor.donations.new') }}" class="text-primary hover:underline dark:text-accent">{{ __('Make your first donation') }}</a>
                        </p>
                    @endforelse
                </div>

                @if ($payments->hasPages())
                    <div class="mt-4">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
