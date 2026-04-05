<x-app-layout title="{{ __('Provider Dashboard') }}" is-header-blur="true">
    {{-- 1. Store status --}}
    <div class="card overflow-hidden mt-4">
        <div class="p-6 text-slate-800 dark:text-navy-100 flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
            <div class="min-w-0">
                <h3 class="text-lg font-bold">{{ __('Store Status') }}</h3>
                <p class="text-sm text-slate-500 dark:text-navy-300">
                    {{ __('Current Status:') }}
                    <span class="font-bold {{ auth()->user()->accepting_orders ? 'text-success' : 'text-error' }}">
                        {{ auth()->user()->accepting_orders ? __('OPEN') : __('PAUSED') }}
                    </span>
                </p>
                <p class="text-xs text-slate-400 dark:text-navy-400 mt-1">
                    {{ auth()->user()->accepting_orders ? __('Your menu is visible to recipients.') : __('Your menu is hidden from recipients until you reopen.') }}
                </p>
                @if (! auth()->user()->is_active)
                    <p class="text-xs text-error mt-2">{{ __('Your account is deactivated by an administrator. Contact support.') }}</p>
                @endif
            </div>

            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:shrink-0">
                <a href="{{ route('provider.profile.edit') }}"
                    class="btn border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                    {{ __('provider.sidebar.hours_notes') }}
                </a>
                <form method="POST" action="{{ route('provider.profile.toggle-active') }}">
                    @csrf
                    <button type="submit" class="btn bg-slate-150 text-slate-800 hover:bg-slate-200 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 w-full sm:w-auto">
                        {{ auth()->user()->accepting_orders ? __('Pause Store') : __('Open Store') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 2. Alerts (needs attention) --}}
    @if (($pendingProofCount ?? 0) > 0 || ($pendingRequestsCount ?? 0) > 0)
        <div class="mt-4 space-y-3">
            @if (($pendingRequestsCount ?? 0) > 0)
                <div class="card border border-warning/30 bg-warning/5 p-4 dark:bg-warning/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex gap-3 items-start">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/20 text-warning" aria-hidden="true">
                                <i class="fa-solid fa-inbox text-lg"></i>
                            </span>
                            <p class="text-sm text-slate-700 dark:text-navy-100 pt-0.5 text-start">
                                {{ __('provider.dashboard.awaiting_alert', ['count' => $pendingRequestsCount]) }}
                            </p>
                        </div>
                        <a href="{{ route('provider.requests.index', ['status' => 'REQUESTED']) }}"
                            class="btn bg-warning/15 text-warning hover:bg-warning/25 text-sm shrink-0 w-full sm:w-auto">
                            {{ __('provider.dashboard.review_requests') }}
                        </a>
                    </div>
                </div>
            @endif
            @if (($pendingProofCount ?? 0) > 0)
                <div class="card border border-error/30 bg-error/5 p-4 dark:bg-error/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex gap-3 items-start">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-error/20 text-error" aria-hidden="true">
                                <i class="fa-solid fa-image text-lg"></i>
                            </span>
                            <p class="text-sm text-slate-700 dark:text-navy-100 pt-0.5 text-start">
                                {{ __('provider.dashboard.proof_alert', ['count' => $pendingProofCount]) }}
                            </p>
                        </div>
                        <a href="{{ route('provider.requests.index', ['needs_proof' => '1']) }}"
                            class="btn bg-error/15 text-error hover:bg-error/25 text-sm shrink-0 w-full sm:w-auto">
                            {{ __('provider.dashboard.upload_proof') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 3. KPI cards (shared layout: icon + metrics row, optional hint, footer) --}}
    @php
        $kpiBtn = 'inline-flex min-h-[2.5rem] w-full flex-1 items-center justify-center rounded-xl px-3 py-2 text-xs font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 dark:focus-visible:ring-accent/30 bg-primary/10 text-primary hover:bg-primary/15 dark:bg-accent/15 dark:text-accent-light dark:hover:bg-accent/25';
    @endphp
    <div class="mt-4">
        <h2 class="mb-4 text-sm font-semibold tracking-wide text-slate-500 dark:text-navy-400">
            {{ __('provider.dashboard.kpi_section') }}
        </h2>
        <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 sm:gap-5 lg:gap-6">
            <x-provider.kpi-stat-card>
                <x-slot name="icon"><i class="fa-solid fa-clock text-[0.95rem]"></i></x-slot>
                <x-slot name="value">
                    <p class="text-xl font-bold tabular-nums leading-none tracking-tight" dir="ltr">{{ $pendingRequestsCount ?? 0 }}</p>
                </x-slot>
                <x-slot name="label">
                    <p class="text-xs-plus leading-snug text-slate-600 dark:text-navy-300">{{ __('provider.dashboard.awaiting_response') }}</p>
                </x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['status' => 'REQUESTED']) }}" class="{{ $kpiBtn }}">{{ __('finance.common.view') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card>
                <x-slot name="icon"><i class="fa-solid fa-file-circle-check text-[0.95rem]"></i></x-slot>
                <x-slot name="value">
                    <p class="text-xl font-bold tabular-nums leading-none tracking-tight" dir="ltr">{{ $pendingProofCount ?? 0 }}</p>
                </x-slot>
                <x-slot name="label">
                    <p class="text-xs-plus leading-snug text-slate-600 dark:text-navy-300">{{ __('Proof pending') }}</p>
                </x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['needs_proof' => '1']) }}" class="{{ $kpiBtn }}">{{ __('finance.common.view') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card>
                <x-slot name="icon"><i class="fa-solid fa-circle-check text-[0.95rem]"></i></x-slot>
                <x-slot name="value">
                    <p class="text-xl font-bold tabular-nums leading-none tracking-tight" dir="ltr">{{ $fulfilledLast30Count ?? 0 }}</p>
                </x-slot>
                <x-slot name="label">
                    <p class="text-xs-plus leading-snug text-slate-600 dark:text-navy-300">{{ __('provider.dashboard.fulfilled_30d') }}</p>
                </x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['status' => 'FULFILLED']) }}" class="{{ $kpiBtn }}">{{ __('finance.common.view') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card>
                <x-slot name="icon"><i class="fa-solid fa-hand-holding-heart text-[0.95rem]"></i></x-slot>
                <x-slot name="value">
                    <p class="text-xl font-bold tabular-nums leading-none tracking-tight" dir="ltr" data-metric="adopted-donor-count">{{ $adoptedRequestsCount ?? 0 }}</p>
                </x-slot>
                <x-slot name="label">
                    <p class="text-xs-plus font-medium leading-snug text-slate-800 dark:text-navy-100">{{ __('provider.dashboard.adoptions_title') }}</p>
                </x-slot>
                <x-slot name="description">
                    <p class="line-clamp-2 text-tiny leading-relaxed">{{ __('provider.dashboard.adoptions_hint') }}</p>
                </x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['funding_source' => 'PROVIDER_ADOPTION']) }}" class="{{ $kpiBtn }}">{{ __('provider.dashboard.adoptions_cta') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card>
                <x-slot name="icon"><i class="fa-solid fa-coins text-[0.95rem]"></i></x-slot>
                <x-slot name="value">
                    <p class="text-xl font-bold tabular-nums leading-none tracking-tight" dir="ltr">
                        {{ number_format($valueFulfilledLast30 ?? 0, 2) }} <span class="text-xs font-semibold text-secondary dark:text-navy-300">{{ __('SAR') }}</span>
                    </p>
                </x-slot>
                <x-slot name="label">
                    <p class="text-xs-plus leading-snug text-slate-600 dark:text-navy-300">{{ __('provider.dashboard.value_delivered_30d') }}</p>
                </x-slot>
                <x-slot name="footer">
                    <div class="grid w-full grid-cols-2 gap-2" role="group" aria-label="{{ __('provider.dashboard.value_delivered_30d') }}">
                        <div
                            class="flex min-h-[4.75rem] min-w-0 flex-col items-center justify-center gap-1 rounded-xl border border-primary/20 bg-primary/5 px-2 py-2.5 text-center shadow-sm dark:border-accent/25 dark:bg-accent/10 dark:shadow-none"
                            title="{{ __('provider.dashboard.in_pipeline') }}">
                            <p class="text-xs font-semibold leading-snug text-slate-600 dark:text-navy-300">{{ __('provider.dashboard.value_kpi_in_pipeline') }}</p>
                            <p class="text-lg font-bold tabular-nums tracking-tight text-slate-900 dark:text-navy-50" dir="ltr">{{ $inPipelineCount ?? 0 }}</p>
                        </div>
                        <div
                            class="flex min-h-[4.75rem] min-w-0 flex-col items-center justify-center gap-1 rounded-xl border border-primary/20 bg-primary/5 px-2 py-2.5 text-center shadow-sm dark:border-accent/25 dark:bg-accent/10 dark:shadow-none"
                            title="{{ __('provider.dashboard.qr_redeemed_30d') }}">
                            <p class="text-xs font-semibold leading-snug text-slate-600 dark:text-navy-300">{{ __('provider.dashboard.value_kpi_qr') }}</p>
                            <p class="text-lg font-bold tabular-nums tracking-tight text-slate-900 dark:text-navy-50" dir="ltr">{{ $qrRedeemedLast30Count ?? 0 }}</p>
                        </div>
                    </div>
                </x-slot>
            </x-provider.kpi-stat-card>
        </div>
    </div>

    {{-- 4. Quick actions (prominent, full width) --}}
    <div class="card mt-4 px-4 pb-4 pt-1 sm:px-5">
        <div class="my-3">
            <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('provider.dashboard.quick_actions') }}</h2>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:items-stretch">
            <a href="{{ route('provider.requests.index') }}" class="btn flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-white shadow-sm dark:bg-accent hover:bg-primary-focus dark:hover:bg-accent-focus">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <span>{{ __('Incoming Requests') }}</span>
            </a>
            <a href="{{ route('provider.menu-items.index') }}" class="btn flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl border-2 border-primary bg-transparent py-3 text-primary shadow-sm hover:bg-primary/10 dark:border-accent dark:text-accent dark:hover:bg-accent/10">
                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                <span>{{ __('Inventory') }}</span>
            </a>
            <a href="{{ route('provider.qr.scan') }}" class="btn flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white py-3 text-slate-800 shadow-sm hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                <span>{{ __('Scan QR Code') }}</span>
            </a>
        </div>
    </div>

    {{-- 5. Chart + recent: stacked; chart stays LTR for numeric axis --}}
    <div class="mt-4 space-y-4 sm:mt-5 sm:space-y-5 lg:mt-6">
        <div class="card px-4 pb-4 sm:px-5">
            <div class="my-3 flex h-8 items-center justify-between gap-2">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100 text-start">
                    {{ __('provider.dashboard.chart_title') }}
                </h2>
            </div>
            <div class="h-[280px] w-full overflow-x-auto overflow-y-hidden" dir="ltr">
                <div class="ax-transparent-gridline h-[280px] min-w-[min(100%,42rem)] pe-2"
                     data-chart-series="{{ json_encode($weeklyFulfilledChart['series'] ?? []) }}"
                     data-chart-categories="{{ json_encode($weeklyFulfilledChart['categories'] ?? []) }}"
                     data-chart-label="{{ json_encode(__('provider.dashboard.chart_series')) }}"
                     data-chart-rtl="{{ app()->getLocale() === 'ar' ? '1' : '0' }}"
                     x-data="{
                        init() {
                            $nextTick(() => {
                                if (this.$el._x_chart) return;
                                const chartHeight = 280;
                                const config = { ...pages.charts.incomePersonal };
                                const series = JSON.parse(this.$el.dataset.chartSeries);
                                const categories = JSON.parse(this.$el.dataset.chartCategories);
                                const label = JSON.parse(this.$el.dataset.chartLabel);
                                const isRtl = this.$el.dataset.chartRtl === '1';
                                config.series = [{ name: label, data: series }];
                                config.colors = ['#f0aa1f'];
                                config.xaxis = { ...config.xaxis, categories, position: 'bottom' };
                                config.chart = {
                                    ...config.chart,
                                    height: chartHeight,
                                    parentHeightOffset: 0,
                                    toolbar: { show: false },
                                    padding: { top: 12, right: 0, bottom: 0, left: 0 },
                                };
                                config.yaxis = { ...config.yaxis, ...(isRtl ? { opposite: true } : {}) };
                                config.dataLabels = {
                                    ...config.dataLabels,
                                    enabled: series.some(v => v > 0),
                                    offsetY: -10,
                                    formatter: function (val) {
                                        return val >= 1000 ? (val / 1000).toFixed(2) + 'k' : val;
                                    },
                                };
                                if (series.every(v => v === 0)) {
                                    config.yaxis = { ...config.yaxis, min: 0, max: 5, forceNiceScale: true, ...(isRtl ? { opposite: true } : {}) };
                                }
                                this.$el._x_chart = new ApexCharts(this.$el, config);
                                this.$el._x_chart.render();
                            });
                        }
                     }" x-init="init()"></div>
            </div>
        </div>

        <div class="card px-4 pb-4 sm:px-5">
            <div class="my-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100 text-start">
                    {{ __('provider.dashboard.recent_requests') }}
                </h2>
                <a href="{{ route('provider.requests.index') }}"
                    class="border-b border-dotted border-current pb-0.5 text-xs-plus font-medium text-primary hover:text-primary/70 dark:text-accent-light">
                    {{ __('provider.dashboard.view_all') }}
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-navy-500 rounded-lg border border-slate-100 dark:border-navy-500 overflow-hidden">
                @forelse($recentRequests as $req)
                    @php
                        $recentRef = \App\Support\PseudonymousRequestId::make($req->id);
                        $recentType = \App\Support\RequestTypeLabel::forRequest($req);
                    @endphp
                    <a href="{{ route('provider.requests.show', $req->id) }}"
                        class="flex flex-col gap-2 p-3 transition-colors hover:bg-slate-50 dark:hover:bg-navy-600/50 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
                        aria-label="{{ __('Request') }} #{{ $req->id }}, {{ $recentRef }}">
                        <div class="min-w-0 flex-1 text-start">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-3">
                                <span class="inline-flex w-fit shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-700 dark:bg-navy-600 dark:text-navy-100" dir="ltr">#{{ $req->id }}</span>
                                <span class="font-medium text-slate-700 dark:text-navy-100 break-words">{{ $recentType }}</span>
                            </div>
                            <div class="mt-1.5 flex w-full justify-start">
                                @include('provider.requests.partials.pseudonymous-ref-inline', ['ref' => $recentRef, 'variant' => 'compact'])
                            </div>
                            <p class="text-tiny text-slate-400 dark:text-navy-500 mt-1" title="{{ $req->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}">
                                {{ __('provider.dashboard.request_created') }} {{ $req->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="shrink-0 self-start rounded-md bg-slate-50 px-2 py-1 text-xs font-medium leading-snug sm:max-w-[12rem] sm:text-end dark:bg-navy-600/80 {{ $req->status === 'FULFILLED' ? 'text-success' : ($req->status === 'REQUESTED' ? 'text-warning' : 'text-primary dark:text-accent-light') }}">
                            {{ $statusLabels[$req->status] ?? str_replace('_', ' ', $req->status) }}
                        </span>
                    </a>
                @empty
                    <div class="p-4 text-sm text-slate-500 dark:text-navy-400 text-center">{{ __('provider.dashboard.no_requests') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
