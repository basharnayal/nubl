<x-app-layout title="{{ __('finance.overview.title') }}" is-header-blur="true">
    <div class="space-y-6 pt-4">
        @include('admin.finances._nav')

        {{-- ============================================================
             PAGE HEADER
        ============================================================ --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-navy-50">
                    {{ __('finance.overview.title') }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">
                    {{ __('finance.overview.intro_wallet') }}
                </p>
            </div>

            {{-- Intro toggle (keeps collapsible help text available without stealing space) --}}
            <details class="group relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-200 dark:hover:border-navy-500 dark:hover:text-navy-50 [&::-webkit-details-marker]:hidden">
                    <i class="fa-regular fa-circle-question text-slate-400 dark:text-navy-300" aria-hidden="true"></i>
                    <span>{{ __('finance.overview.intro_title') }}</span>
                    <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform duration-200 group-open:rotate-180 dark:text-navy-400" aria-hidden="true"></i>
                </summary>
                <div class="absolute end-0 z-20 mt-2 w-[22rem] max-w-[92vw] rounded-xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-900/5 dark:border-navy-600 dark:bg-navy-800 dark:shadow-black/30">
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
        </div>

        {{-- ============================================================
             HERO + KPIs
             Hero wallet card + 3 supporting metric tiles
        ============================================================ --}}
        @php
            $successAmount      = (float) ($overview['successful_payments_amount'] ?? 0);
            $failedAmount       = (float) ($overview['unsuccessful_payments_amount'] ?? 0);
            $totalPayments      = $successAmount + $failedAmount;
            $successRate        = $totalPayments > 0 ? ($successAmount / $totalPayments) * 100 : 0;
            $successCount       = (int) ($overview['successful_payments_count'] ?? 0);
            $failedCount        = (int) ($overview['unsuccessful_payments_count'] ?? 0);
            $totalCount         = $successCount + $failedCount;
        @endphp

        <section aria-labelledby="finance-kpi-heading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <h2 id="finance-kpi-heading" class="sr-only">{{ __('finance.overview.section_kpi') }}</h2>
            {{-- HERO: System wallet balance --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800 sm:p-7">
                {{-- Subtle accent bar --}}
                <span class="absolute inset-y-0 start-0 w-1 bg-gradient-to-b from-primary to-primary/40 dark:from-accent dark:to-accent/40" aria-hidden="true"></span>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                            <i class="fa-solid fa-wallet text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-600 dark:text-navy-200">
                                {{ __('finance.overview.system_wallet_balance') }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-navy-400">
                                {{ __('finance.common.sar') }}
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        {{ __('finance.overview.records_count', ['count' => $overview['successful_payments_count'] + $overview['unsuccessful_payments_count']]) }}
                    </span>
                </div>

                <p class="mt-6 flex items-baseline gap-2 text-3xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50 sm:text-4xl">
                    {{ number_format($overview['system_wallet_balance'], 2) }}
                    <span class="text-base font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                </p>

                {{-- Inline mini-context: in / out --}}
                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 dark:border-navy-700">
                    <div>
                        <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                            <i class="fa-solid fa-arrow-down text-[10px] text-emerald-500" aria-hidden="true"></i>
                            {{ __('finance.overview.ledger_in_system') }}
                        </p>
                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-800 dark:text-navy-100">
                            {{ number_format($overview['fund_inbound_system'], 2) }}
                            <span class="text-xs font-normal text-slate-400 dark:text-navy-400">{{ __('finance.common.sar') }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-navy-300">
                            <i class="fa-solid fa-arrow-up text-[10px] text-rose-500" aria-hidden="true"></i>
                            {{ __('finance.overview.ledger_out_system') }}
                        </p>
                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-800 dark:text-navy-100">
                            {{ number_format($overview['fund_outbound_system'], 2) }}
                            <span class="text-xs font-normal text-slate-400 dark:text-navy-400">{{ __('finance.common.sar') }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Successful payments --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        {{ number_format($successRate, 1) }}%
                    </span>
                </div>
                <p class="mt-5 text-sm font-medium text-slate-600 dark:text-navy-200">
                    {{ __('finance.overview.successful_payments_total') }}
                </p>
                <p class="mt-2 flex items-baseline gap-1.5 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50">
                    {{ number_format($overview['successful_payments_amount'], 2) }}
                    <span class="text-sm font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                </p>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-navy-700 dark:text-navy-300">
                    <span>{{ __('finance.overview.records_count', ['count' => $successCount]) }}</span>
                    <a href="{{ route('admin.finances.payments.index') }}" class="font-medium text-primary hover:underline dark:text-accent-light">
                        {{ __('finance.nav.payments') }}
                        <i class="fa-solid fa-arrow-right ms-0.5 text-[10px] rtl:rotate-180" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            {{-- Failed payments --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                        <i class="fa-solid fa-triangle-exclamation text-sm" aria-hidden="true"></i>
                    </span>
                    @if($totalCount > 0)
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                            {{ number_format(100 - $successRate, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-5 text-sm font-medium text-slate-600 dark:text-navy-200">
                    {{ __('finance.overview.failed_payments') }}
                </p>
                <p class="mt-2 flex items-baseline gap-1.5 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50">
                    {{ number_format($overview['unsuccessful_payments_amount'], 2) }}
                    <span class="text-sm font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                </p>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-navy-700 dark:text-navy-300">
                    <span>{{ __('finance.overview.records_count', ['count' => $failedCount]) }}</span>
                    <a href="{{ route('admin.finances.payments.index', ['status' => 'PROBLEM_GROUP']) }}" class="font-medium text-rose-600 hover:underline dark:text-rose-400">
                        {{ __('finance.overview.quick_problem') }}
                        <i class="fa-solid fa-arrow-right ms-0.5 text-[10px] rtl:rotate-180" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- ============================================================
             CHARTS
        ============================================================ --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            {{-- Gateway payments by outcome --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5 dark:border-navy-700 sm:p-6">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-navy-50">
                            {{ __('finance.overview.chart_payments_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">
                            {{ __('finance.overview.chart_payments_subtitle') }}
                        </p>
                    </div>
                    @if($chartPayments['has_data'])
                        <div class="shrink-0 text-end">
                            <p class="text-xs text-slate-400 dark:text-navy-400">{{ __('finance.overview.records_count', ['count' => $totalCount]) }}</p>
                            <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-navy-50">
                                {{ number_format($successRate, 1) }}%
                            </p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('finance.overview.chart_legend_successful') }}</p>
                        </div>
                    @endif
                </div>
                <div class="p-5 sm:p-6">
                    @if($chartPayments['has_data'])
                        <div id="finance-chart-payments" class="-mx-1 min-h-[280px]"></div>
                        <script type="application/json" id="finance-chart-payments-data">@json($chartPayments['config'])</script>
                    @else
                        <div class="flex min-h-[280px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 dark:border-navy-600 dark:bg-navy-900/30">
                            <div class="px-6 text-center">
                                <i class="fa-regular fa-chart-pie text-2xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                                <p class="mt-2 text-sm text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_empty_payments') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ledger movement --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5 dark:border-navy-700 sm:p-6">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-navy-50">
                            {{ __('finance.overview.chart_ledger_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">
                            {{ __('finance.overview.chart_ledger_subtitle') }}
                        </p>
                    </div>
                    <a href="{{ route('admin.finances.fund-transactions.index') }}"
                       class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900 dark:border-navy-600 dark:text-navy-200 dark:hover:border-navy-500 dark:hover:text-navy-50">
                        {{ __('finance.nav.fund_ledger') }}
                        <i class="fa-solid fa-arrow-right ms-1 text-[10px] rtl:rotate-180" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="p-5 sm:p-6">
                    @if($chartLedger['has_data'])
                        <div id="finance-chart-ledger" class="-mx-1 min-h-[260px]"></div>
                        <script type="application/json" id="finance-chart-ledger-data">@json($chartLedger['config'])</script>
                    @else
                        <div class="flex min-h-[260px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 dark:border-navy-600 dark:bg-navy-900/30">
                            <div class="px-6 text-center">
                                <i class="fa-regular fa-chart-bar text-2xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                                <p class="mt-2 text-sm text-slate-500 dark:text-navy-400">{{ __('finance.overview.chart_empty_ledger') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================================================
             LEDGER SUPPORTING FIGURES
        ============================================================ --}}
        <section aria-labelledby="finance-secondary-heading">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="finance-secondary-heading" class="text-sm font-semibold text-slate-700 dark:text-navy-100">
                    {{ __('finance.overview.section_secondary_title') }}
                </h2>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-navy-600 dark:bg-navy-800 dark:hover:border-navy-500">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
                        </span>
                        <p class="text-sm font-medium text-slate-600 dark:text-navy-200">{{ __('finance.overview.ledger_in_system') }}</p>
                    </div>
                    <p class="mt-3 text-xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50">
                        {{ number_format($overview['fund_inbound_system'], 2) }}
                        <span class="text-sm font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-navy-600 dark:bg-navy-800 dark:hover:border-navy-500">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                            <i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i>
                        </span>
                        <p class="text-sm font-medium text-slate-600 dark:text-navy-200">{{ __('finance.overview.ledger_out_system') }}</p>
                    </div>
                    <p class="mt-3 text-xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50">
                        {{ number_format($overview['fund_outbound_system'], 2) }}
                        <span class="text-sm font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-navy-600 dark:bg-navy-800 dark:hover:border-navy-500">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400">
                            <i class="fa-solid fa-building-columns text-xs" aria-hidden="true"></i>
                        </span>
                        <p class="text-sm font-medium text-slate-600 dark:text-navy-200">{{ __('finance.overview.transfers_to_providers') }}</p>
                    </div>
                    <p class="mt-3 text-xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-navy-50">
                        {{ number_format($overview['transfers_to_providers'], 2) }}
                        <span class="text-sm font-medium text-slate-400 dark:text-navy-300">{{ __('finance.common.sar') }}</span>
                    </p>
                </div>
            </div>
        </section>

        {{-- ============================================================
             QUICK ACTIONS / EXPORTS
        ============================================================ --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800" aria-labelledby="finance-actions-heading">
            <div class="flex flex-col gap-1 border-b border-slate-100 p-5 dark:border-navy-700 sm:p-6">
                <h2 id="finance-actions-heading" class="text-base font-semibold text-slate-900 dark:text-navy-50">
                    {{ __('finance.overview.section_actions_title') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-navy-300">
                    {{ __('finance.nav.payments') }} · {{ __('finance.nav.fund_ledger') }}
                </p>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-navy-700">
                {{-- Problem filter row --}}
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                            <i class="fa-solid fa-triangle-exclamation text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-navy-50">
                                {{ __('finance.overview.quick_problem_label') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">
                                {{ __('finance.overview.failed_payments_scope_note') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.finances.payments.index', ['status' => 'PROBLEM_GROUP']) }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/15">
                        <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                        {{ __('finance.overview.quick_problem') }}
                    </a>
                </div>

                {{-- Payments exports row --}}
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-200">
                            <i class="fa-solid fa-credit-card text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-navy-50">
                                {{ __('finance.nav.payments') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">
                                {{ __('finance.overview.successful_payments_total') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.finances.payments.export') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-500 dark:hover:bg-navy-700">
                            <i class="fa-solid fa-file-csv text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                            CSV
                        </a>
                        <a href="{{ route('admin.finances.payments.export-pdf') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-500 dark:hover:bg-navy-700">
                            <i class="fa-solid fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                            PDF
                        </a>
                    </div>
                </div>

                {{-- Fund ledger exports row --}}
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-200">
                            <i class="fa-solid fa-book text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-navy-50">
                                {{ __('finance.nav.fund_ledger') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">
                                {{ __('finance.overview.chart_ledger_subtitle') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.finances.fund-transactions.export') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-500 dark:hover:bg-navy-700">
                            <i class="fa-solid fa-file-csv text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                            CSV
                        </a>
                        <a href="{{ route('admin.finances.fund-transactions.export-pdf') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-500 dark:hover:bg-navy-700">
                            <i class="fa-solid fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
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
