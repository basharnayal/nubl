<x-app-layout title="{{ __('My Donations') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="col-span-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                        {{ __('My Donations & Impact') }}
                    </h2>
                    <a href="{{ route('donor.donations.new') }}" class="btn inline-flex shrink-0 items-center gap-2 bg-primary px-4 py-2.5 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                        <i class="fa-solid fa-heart"></i>
                        <span>{{ __('New Donation') }}</span>
                    </a>
                </div>

                <div class="card mt-3 p-6">
                    @forelse ($payments as $payment)
                        <div class="border-b border-slate-200 py-4 last:border-0 dark:border-navy-600">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-slate-700 dark:text-navy-100">
                                            <x-sar-symbol /> {{ number_format($payment->amount, 2) }}
                                        </p>
                                        @php
                                            $statusConfig = match($payment->status) {
                                                \App\Models\Payment::STATUS_SUCCEEDED => ['label' => __('Completed'), 'class' => 'bg-success/10 text-success'],
                                                \App\Models\Payment::STATUS_PENDING, \App\Models\Payment::STATUS_PROCESSING, \App\Models\Payment::STATUS_INITIATED => ['label' => __('Pending'), 'class' => 'bg-warning/10 text-warning'],
                                                \App\Models\Payment::STATUS_FAILED => ['label' => __('Failed'), 'class' => 'bg-error/10 text-error'],
                                                default => ['label' => $payment->status, 'class' => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-300'],
                                            };
                                        @endphp
                                        <span class="badge {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-navy-400">
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
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="flex size-16 items-center justify-center rounded-full bg-slate-100 dark:bg-navy-600">
                                <i class="fa-solid fa-heart text-2xl text-slate-400 dark:text-navy-400"></i>
                            </div>
                            <p class="mt-4 text-center text-slate-500 dark:text-navy-400">
                                {{ __('No donations yet.') }}
                            </p>
                            <a href="{{ route('donor.donations.new') }}" class="btn mt-4 inline-flex items-center gap-2 bg-primary px-5 py-2.5 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                                <i class="fa-solid fa-heart"></i>
                                <span>{{ __('Make your first donation') }}</span>
                            </a>
                        </div>
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
