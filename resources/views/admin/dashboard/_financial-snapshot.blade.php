{{--
    FINANCIAL SNAPSHOT — Compact horizontal strip of key financial indicators.
    Reuses AdminFinancialService::getOverview() — no duplicate queries.
--}}
@php
    $walletBalance = (float) ($financial['system_wallet_balance']      ?? 0);
    $successCount  = (int)   ($financial['successful_payments_count']  ?? 0);
    $successAmount = (float) ($financial['successful_payments_amount'] ?? 0);
    $pendingCount  = (int)   ($financial['pending_count']              ?? 0);
    $pendingAmount = (float) ($financial['pending_amount']             ?? 0);
    $failedCount   = (int)   ($financial['failed_count']               ?? 0);
    $failedAmount  = (float) ($financial['failed_amount']              ?? 0);
    $fundIn        = (float) ($financial['fund_inbound_system']        ?? 0);
    $payoutsOut    = (float) ($financial['transfers_to_providers']     ?? 0);
@endphp

<section aria-labelledby="finance-snapshot-heading">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-navy-700">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <i class="fa-solid fa-coins text-sm" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 id="finance-snapshot-heading" class="font-semibold text-slate-800 dark:text-navy-50">
                        {{ __('dashboard.financial.title') }}
                    </h2>
                    <p class="text-xs text-slate-400 dark:text-navy-400">
                        {{ __('dashboard.financial.subtitle') }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.finances.overview') }}"
               class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-200 dark:hover:border-navy-500 dark:hover:bg-navy-600">
                {{ __('dashboard.financial.full_overview') }} →
            </a>
        </div>

        {{-- Stats grid (6 cells, dividers between them) --}}
        <div class="grid grid-cols-2 divide-y divide-slate-100 dark:divide-navy-700 sm:grid-cols-3 lg:grid-cols-6 sm:divide-y-0 sm:divide-x">

            {{-- System Wallet (hero cell, accent bar on start) --}}
            <div class="relative col-span-2 flex flex-col gap-1 border-b border-slate-100 px-5 py-4 dark:border-navy-700 sm:col-span-1 sm:border-b-0">
                <span class="absolute inset-y-0 start-0 w-1 rounded-es-none rounded-ss-2xl bg-gradient-to-b from-emerald-500 to-emerald-400/40"
                      aria-hidden="true"></span>
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-wallet text-[10px] text-emerald-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.system_wallet') }}
                </p>
                <x-sar-amount
                    class="text-xl font-bold tabular-nums tracking-tight text-emerald-700 dark:text-emerald-300"
                    :value="number_format($walletBalance, 2)"
                />
                <p class="text-[10px] text-slate-400 dark:text-navy-500">{{ __('dashboard.financial.wallet_sub') }}</p>
            </div>

            {{-- Successful --}}
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 dark:border-navy-700 sm:border-b-0">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-circle-check text-[10px] text-emerald-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.successful') }}
                </p>
                <x-sar-amount class="text-lg font-bold tabular-nums text-slate-900 dark:text-navy-50" :value="number_format($successAmount, 0)" />
                <p class="text-[10px] text-slate-400 dark:text-navy-500">
                    {{ trans_choice('dashboard.financial.payments_count', $successCount, ['count' => number_format($successCount)]) }}
                </p>
            </div>

            {{-- Pending --}}
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 dark:border-navy-700 sm:border-b-0">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-hourglass-half text-[10px] text-amber-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.pending') }}
                </p>
                <x-sar-amount class="text-lg font-bold tabular-nums text-slate-900 dark:text-navy-50" :value="number_format($pendingAmount, 0)" />
                <p class="text-[10px] text-slate-400 dark:text-navy-500">
                    {{ __('dashboard.financial.pending_sub', ['count' => number_format($pendingCount)]) }}
                </p>
            </div>

            {{-- Failed --}}
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 dark:border-navy-700 sm:border-b-0">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-circle-xmark text-[10px] text-rose-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.failed') }}
                </p>
                <x-sar-amount class="text-lg font-bold tabular-nums {{ $failedCount > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-slate-900 dark:text-navy-50' }}" :value="number_format($failedAmount, 0)" />
                <p class="text-[10px] text-slate-400 dark:text-navy-500">
                    {{ trans_choice('dashboard.financial.failed_sub', $failedCount, ['count' => number_format($failedCount)]) }}
                </p>
            </div>

            {{-- Ledger IN --}}
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 dark:border-navy-700 sm:border-b-0">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-arrow-down text-[10px] text-emerald-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.ledger_in') }}
                </p>
                <x-sar-amount class="text-lg font-bold tabular-nums text-slate-900 dark:text-navy-50" :value="number_format($fundIn, 0)" />
                <p class="text-[10px] text-slate-400 dark:text-navy-500">{{ __('dashboard.financial.ledger_in_sub') }}</p>
            </div>

            {{-- Provider Payouts --}}
            <div class="flex flex-col gap-1 px-5 py-4">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                    <i class="fa-solid fa-arrow-up text-[10px] text-rose-500" aria-hidden="true"></i>
                    {{ __('dashboard.financial.provider_payouts') }}
                </p>
                <x-sar-amount class="text-lg font-bold tabular-nums text-slate-900 dark:text-navy-50" :value="number_format($payoutsOut, 0)" />
                <a href="{{ route('admin.finances.provider-payouts.index') }}"
                   class="text-[10px] font-medium text-primary hover:underline dark:text-accent-light">
                    {{ __('dashboard.financial.view_payouts') }} →
                </a>
            </div>

        </div>
    </div>
</section>
