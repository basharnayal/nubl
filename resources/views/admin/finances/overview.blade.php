<x-app-layout title="{{ __('finance.overview.title') }}" is-header-blur="true">
    <div class="space-y-8 pt-4">
        @include('admin.finances._nav')

        {{-- A. Intro (collapsible) --}}
        <details class="card group border border-slate-200/80 bg-slate-50/80 dark:border-navy-600 dark:bg-navy-800/40">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-start focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 sm:px-6 sm:py-5 [&::-webkit-details-marker]:hidden">
                <span class="text-base font-semibold text-slate-800 dark:text-navy-50">{{ __('finance.overview.intro_title') }}</span>
                <i class="fa-solid fa-chevron-down text-sm text-slate-400 transition-transform duration-200 group-open:rotate-180 dark:text-navy-400" aria-hidden="true"></i>
            </summary>
            <div class="border-t border-slate-200 px-5 pb-5 pt-1 dark:border-navy-600 sm:px-6 sm:pb-6">
                <ul class="space-y-3 text-sm leading-relaxed text-slate-600 dark:text-navy-200">
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary dark:bg-accent" aria-hidden="true"></span>
                        <span>{{ __('finance.overview.intro_gateway') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400 dark:bg-navy-400" aria-hidden="true"></span>
                        <span>{{ __('finance.overview.intro_ledger') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500/90" aria-hidden="true"></span>
                        <span>{{ __('finance.overview.intro_wallet') }}</span>
                    </li>
                </ul>
            </div>
        </details>

        {{-- B. Main KPIs --}}
        <section aria-labelledby="finance-kpi-heading">
            <h2 id="finance-kpi-heading" class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                {{ __('finance.overview.section_kpi') }}
            </h2>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-stretch">
                <div class="lg:col-span-5">
                    <div class="flex h-full min-h-[11rem] flex-col justify-between rounded-xl border-2 border-primary/35 bg-gradient-to-br from-white via-white to-primary/[0.06] p-6 shadow-sm dark:border-accent/40 dark:from-navy-800 dark:via-navy-800 dark:to-accent/[0.08]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary dark:text-accent-light">
                            {{ __('finance.overview.system_wallet_balance') }}
                        </p>
                        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-navy-50 sm:text-4xl">
                            {{ number_format($overview['system_wallet_balance'], 2) }}
                            <span class="text-xl font-semibold text-slate-600 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:col-span-7">
                    <div class="rounded-xl border border-emerald-200/60 bg-white p-4 shadow-sm dark:border-emerald-900/35 dark:bg-navy-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('finance.overview.successful_payments_total') }}</p>
                        <p class="mt-2 text-xl font-semibold tabular-nums text-slate-800 dark:text-navy-50">
                            {{ number_format($overview['successful_payments_amount'], 2) }} {{ __('finance.common.sar') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('finance.overview.records_count', ['count' => $overview['successful_payments_count']]) }}</p>
                    </div>
                    <div class="rounded-xl border border-error/30 bg-white p-4 shadow-sm dark:border-error/35 dark:bg-navy-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-error">{{ __('finance.overview.failed_payments') }}</p>
                        <p class="mt-1 text-xs leading-snug text-slate-500 dark:text-navy-400">{{ __('finance.overview.failed_payments_scope_note') }}</p>
                        <p class="mt-2 text-xl font-semibold tabular-nums text-error">
                            {{ number_format($overview['unsuccessful_payments_amount'], 2) }} {{ __('finance.common.sar') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('finance.overview.records_count', ['count' => $overview['unsuccessful_payments_count']]) }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- C + D. Charts; ledger column bundles secondary metrics --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="card p-5 sm:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('finance.overview.chart_payments_title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_payments_subtitle') }}</p>
                </div>
                @if($chartPayments['has_data'])
                    <div id="finance-chart-payments" class="-mx-1 min-h-[280px]"></div>
                    <script type="application/json" id="finance-chart-payments-data">@json($chartPayments['config'])</script>
                @else
                    <div class="flex min-h-[220px] items-center justify-center rounded-lg bg-slate-50 dark:bg-navy-800/30">
                        <p class="px-4 text-center text-sm text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_empty_payments') }}</p>
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-4">
                <div class="card p-5 sm:p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('finance.overview.chart_ledger_title') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_ledger_subtitle') }}</p>
                    </div>
                    @if($chartLedger['has_data'])
                        <div id="finance-chart-ledger" class="-mx-1 min-h-[260px]"></div>
                        <script type="application/json" id="finance-chart-ledger-data">@json($chartLedger['config'])</script>
                    @else
                        <div class="flex min-h-[220px] items-center justify-center rounded-lg bg-slate-50 dark:bg-navy-800/30">
                            <p class="px-4 text-center text-sm text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_empty_ledger') }}</p>
                        </div>
                    @endif
                </div>

                <section aria-labelledby="finance-secondary-heading">
                    <h2 id="finance-secondary-heading" class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                        {{ __('finance.overview.section_secondary_title') }}
                    </h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200/90 bg-slate-50/90 p-3.5 dark:border-navy-600 dark:bg-navy-800/60">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('finance.overview.ledger_in_system') }}</p>
                            <p class="mt-1.5 text-base font-semibold tabular-nums text-slate-800 dark:text-navy-50">
                                {{ number_format($overview['fund_inbound_system'], 2) }} {{ __('finance.common.sar') }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-slate-200/90 bg-slate-50/90 p-3.5 dark:border-navy-600 dark:bg-navy-800/60">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('finance.overview.ledger_out_system') }}</p>
                            <p class="mt-1.5 text-base font-semibold tabular-nums text-slate-800 dark:text-navy-50">
                                {{ number_format($overview['fund_outbound_system'], 2) }} {{ __('finance.common.sar') }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-slate-200/90 bg-slate-50/90 p-3.5 dark:border-navy-600 dark:bg-navy-800/60">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('finance.overview.transfers_to_providers') }}</p>
                            <p class="mt-1.5 text-base font-semibold tabular-nums text-slate-800 dark:text-navy-50">
                                {{ number_format($overview['transfers_to_providers'], 2) }} {{ __('finance.common.sar') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- E. Quick actions --}}
        <section class="card p-5 sm:p-6" aria-labelledby="finance-actions-heading">
            <h2 id="finance-actions-heading" class="mb-5 text-base font-semibold text-slate-800 dark:text-navy-100">
                {{ __('finance.overview.section_actions_title') }}
            </h2>
            <div class="flex flex-wrap items-start gap-4">

                {{-- Problem filter --}}
                <div class="flex flex-col gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">
                        {{ __('finance.overview.quick_problem_label') }}
                    </p>
                    <x-lineone-button :href="route('admin.finances.payments.index', ['status' => 'PROBLEM_GROUP'])" variant="danger" :outline="true" size="sm">
                        <i class="fa-solid fa-triangle-exclamation me-1.5"></i>
                        {{ __('finance.overview.quick_problem') }}
                    </x-lineone-button>
                </div>

                <div class="hidden h-10 w-px self-center bg-slate-200 dark:bg-navy-600 sm:block"></div>

                {{-- Payments exports --}}
                <div class="flex flex-col gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">
                        <i class="fa-solid fa-credit-card me-1"></i>{{ __('finance.nav.payments') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.finances.payments.export') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border-2 border-emerald-500 px-3 py-1.5 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-500/70 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                            <i class="fa-solid fa-file-csv text-base"></i>
                            CSV
                        </a>
                        <a href="{{ route('admin.finances.payments.export-pdf') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border-2 border-error px-3 py-1.5 text-sm font-medium text-error transition-colors hover:bg-error/5 dark:border-error/70 dark:hover:bg-error/10">
                            <i class="fa-solid fa-file-pdf text-base"></i>
                            PDF
                        </a>
                    </div>
                </div>

                <div class="hidden h-10 w-px self-center bg-slate-200 dark:bg-navy-600 sm:block"></div>

                {{-- Ledger exports --}}
                <div class="flex flex-col gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">
                        <i class="fa-solid fa-book me-1"></i>{{ __('finance.nav.fund_ledger') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.finances.fund-transactions.export') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border-2 border-emerald-500 px-3 py-1.5 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-50 dark:border-emerald-500/70 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                            <i class="fa-solid fa-file-csv text-base"></i>
                            CSV
                        </a>
                        <a href="{{ route('admin.finances.fund-transactions.export-pdf') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border-2 border-error px-3 py-1.5 text-sm font-medium text-error transition-colors hover:bg-error/5 dark:border-error/70 dark:hover:bg-error/10">
                            <i class="fa-solid fa-file-pdf text-base"></i>
                            PDF
                        </a>
                    </div>
                </div>

            </div>
        </section>
    </div>

    @if($chartPayments['has_data'] || $chartLedger['has_data'])
        <script>
        (function() {
            function initFinanceCharts() {
                var payEl = document.getElementById('finance-chart-payments');
                var payDataEl = document.getElementById('finance-chart-payments-data');
                var ledEl = document.getElementById('finance-chart-ledger');
                var ledDataEl = document.getElementById('finance-chart-ledger-data');
                if (typeof ApexCharts === 'undefined') {
                    return false;
                }
                try {
                    if (payEl && payDataEl) {
                        var payCfg = JSON.parse(payDataEl.textContent);
                        if (payEl._chart) { payEl._chart.destroy(); }
                        payEl._chart = new ApexCharts(payEl, payCfg);
                        payEl._chart.render();
                    }
                    if (ledEl && ledDataEl) {
                        var ledCfg = JSON.parse(ledDataEl.textContent);
                        if (ledEl._chart) { ledEl._chart.destroy(); }
                        ledEl._chart = new ApexCharts(ledEl, ledCfg);
                        ledEl._chart.render();
                    }
                } catch (e) {
                    console.warn('Finance overview charts:', e);
                }
                return true;
            }
            var retries = 0;
            function tryInit() {
                if (initFinanceCharts()) return;
                if (retries++ < 40) setTimeout(tryInit, 50);
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', tryInit);
            } else {
                tryInit();
            }
        })();
        </script>
    @endif
</x-app-layout>
