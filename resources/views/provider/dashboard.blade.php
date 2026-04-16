<x-app-layout title="{{ __('Provider Dashboard') }}" is-header-blur="true">
    {{-- 1. Store Header & Status --}}
    <div class="px-1 mt-6 mb-8 flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-navy-100">{{ __('Welcome back,') }}
                {{ auth()->user()->name }}
            </h1>
            <p class="text-sm text-slate-400 dark:text-navy-300 mt-1 max-w-lg">
                {{ __('Your store is currently') }}
                <span class="font-bold {{ auth()->user()->accepting_orders ? 'text-success' : 'text-error' }}">
                    {{ auth()->user()->accepting_orders ? __('OPEN') : __('PAUSED') }}
                </span>.
                @if (auth()->user()->accepting_orders)
                    {{ __('Your menu is visible to beneficiaries.') }}
                @else
                    {{ __('Your menu is hidden until you reopen.') }}
                @endif
            </p>
            @if (!auth()->user()->is_active)
                <div
                    class="flex items-center gap-2 mt-3 text-xs font-bold text-error bg-error/10 w-fit px-3 py-1.5 rounded-lg border border-error/20">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ __('Your account is deactivated. Contact support.') }}
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('provider.profile.edit') }}"
                class="btn border border-slate-200 bg-white px-4 py-2.5 rounded-xl text-xs+ font-bold text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 shadow-sm transition-all">
                <i class="fa-solid fa-clock-rotate-left me-2 text-slate-400"></i>
                {{ __('provider.sidebar.hours_notes') }}
            </a>
            <form method="POST" action="{{ route('provider.profile.toggle-active') }}">
                @csrf
                <button type="submit"
                    class="btn bg-primary text-white hover:bg-primary-focus px-5 py-2.5 rounded-xl text-xs+ font-bold shadow-sm transition-all focus:ring-2 focus:ring-primary/20">
                    <i class="fa-solid {{ auth()->user()->accepting_orders ? 'fa-pause' : 'fa-play' }} me-2"></i>
                    {{ auth()->user()->accepting_orders ? __('Pause Store') : __('Open Store') }}
                </button>
            </form>
        </div>
    </div>

    {{-- 2. Tasks / Alerts Section --}}
    @if (($pendingProofCount ?? 0) > 0 || ($pendingRequestsCount ?? 0) > 0)
        <div class="mt-8 flex flex-col gap-1">
            <div class="flex items-center gap-2 px-1 mb-3">
                <i class="fa-regular fa-calendar-check text-slate-400"></i>
                <h2 class="text-sm font-bold tracking-wide text-slate-600 dark:text-navy-100 uppercase">
                    {{ __('Tasks required of you') }}
                </h2>
                @php $totalTasks = ($pendingRequestsCount > 0 ? 1 : 0) + ($pendingProofCount > 0 ? 1 : 0); @endphp
                @if($totalTasks > 0)
                    <span class="rounded-full bg-error/10 px-2 py-0.5 text-[10px] font-bold text-error">
                        {{ __('Urgent') }} {{ $totalTasks }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @if (($pendingRequestsCount ?? 0) > 0)
                    <div class="card p-5 flex flex-col justify-between border-0 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-warning/10 text-warning">
                                <i class="fa-solid fa-inbox text-lg"></i>
                            </div>
                            <span class="rounded-md bg-warning/10 px-2 py-1 text-[10px] font-bold text-warning uppercase">
                                {{ __('Pending') }}
                            </span>
                        </div>
                        <div class="mt-4 flex-1">
                            <h3 class="font-bold text-slate-700 dark:text-navy-100">{{ __('Incoming Requests') }}</h3>
                            <p class="mt-1 text-xs text-slate-400 dark:text-navy-400 leading-relaxed">
                                {{ __('provider.dashboard.awaiting_alert', ['count' => $pendingRequestsCount]) }}
                            </p>
                        </div>
                        <div class="mt-5">
                            <a href="{{ route('provider.requests.index', ['status' => 'REQUESTED']) }}"
                                class="btn w-full bg-warning/10 text-warning hover:bg-warning/20 font-bold text-xs py-2.5 rounded-xl transition-colors">
                                {{ __('provider.dashboard.review_requests') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if (($pendingProofCount ?? 0) > 0)
                    <div class="card p-5 flex flex-col justify-between border-0 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-error/10 text-error">
                                <i class="fa-solid fa-image text-lg"></i>
                            </div>
                            <span class="rounded-md bg-error/10 px-2 py-1 text-[10px] font-bold text-error uppercase">
                                {{ __('Urgent') }}
                            </span>
                        </div>
                        <div class="mt-4 flex-1">
                            <h3 class="font-bold text-slate-700 dark:text-navy-100">{{ __('Proof Required') }}</h3>
                            <p class="mt-1 text-xs text-slate-400 dark:text-navy-400 leading-relaxed">
                                {{ __('provider.dashboard.proof_alert', ['count' => $pendingProofCount]) }}
                            </p>
                        </div>
                        <div class="mt-5">
                            <a href="{{ route('provider.requests.index', ['needs_proof' => '1']) }}"
                                class="btn w-full bg-error/10 text-error hover:bg-error/20 font-bold text-xs py-2.5 rounded-xl transition-colors">
                                {{ __('provider.dashboard.upload_proof') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- 3. KPI cards (shared layout: icon + metrics row, optional hint, footer) --}}
    {{-- 3. KPI cards --}}
    @php
        $kpiBtnBase = 'inline-flex min-h-[2.5rem] w-full flex-1 items-center justify-center rounded-xl p-2 text-[10px] font-bold uppercase transition';
    @endphp
    <div class="mt-8">
        <div class="flex items-center gap-2 px-1 mb-3">
            <i class="fa-solid fa-chart-line text-slate-400"></i>
            <h2 class="text-sm font-bold tracking-wide text-slate-600 dark:text-navy-100 uppercase">
                {{ __('provider.dashboard.kpi_section') }}
            </h2>
        </div>
        <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Awaiting Response and Proof pending KPIs removed - redundancy with Tasks section --}}

            <x-provider.kpi-stat-card iconBgClass="bg-success/10 text-success">
                <x-slot name="icon"><i class="fa-solid fa-circle-check"></i></x-slot>
                <x-slot name="value">{{ $fulfilledLast30Count ?? 0 }}</x-slot>
                <x-slot name="label">{{ __('provider.dashboard.fulfilled_30d') }}</x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['status' => 'FULFILLED']) }}"
                        class="{{ $kpiBtnBase }} bg-success/10 text-success hover:bg-success/20">{{ __('finance.common.view') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card iconBgClass="bg-primary/10 text-primary">
                <x-slot name="icon"><i class="fa-solid fa-hand-holding-heart"></i></x-slot>
                <x-slot name="value"><span
                        data-metric="adopted-donor-count">{{ $adoptedRequestsCount ?? 0 }}</span></x-slot>
                <x-slot name="label">{{ __('provider.dashboard.adoptions_title') }}</x-slot>
                <x-slot name="description">{{ __('provider.dashboard.adoptions_hint') }}</x-slot>
                <x-slot name="footer">
                    <a href="{{ route('provider.requests.index', ['funding_source' => 'PROVIDER_ADOPTION']) }}"
                        class="{{ $kpiBtnBase }} bg-primary/10 text-primary hover:bg-primary/20">{{ __('provider.dashboard.adoptions_cta') }}</a>
                </x-slot>
            </x-provider.kpi-stat-card>

            <x-provider.kpi-stat-card iconBgClass="bg-indigo-500/10 text-indigo-500">
                <x-slot name="icon"><i class="fa-solid fa-coins"></i></x-slot>
                <x-slot name="value">
                    <span class="shrink-0">{{ number_format($valueFulfilledLast30 ?? 0, 0) }}</span>
                    <span class="ms-1 text-[10px] uppercase opacity-70">{{ __('SAR') }}</span>
                </x-slot>
                <x-slot name="label">{{ __('provider.dashboard.value_delivered_30d') }}</x-slot>
                <x-slot name="footer">
                    <div class="grid w-full grid-cols-2 gap-2" role="group">
                        <div
                            class="flex flex-col items-center justify-center rounded-xl bg-indigo-500/5 py-2 dark:bg-navy-600">
                            <span
                                class="text-[10px] font-bold uppercase text-indigo-500/60 leading-none mb-1">{{ __('Pipeline') }}</span>
                            <span
                                class="text-sm font-bold text-indigo-600 dark:text-navy-50">{{ $inPipelineCount ?? 0 }}</span>
                        </div>
                        <div
                            class="flex flex-col items-center justify-center rounded-xl bg-indigo-500/5 py-2 dark:bg-navy-600">
                            <span
                                class="text-[10px] font-bold uppercase text-indigo-500/60 leading-none mb-1">{{ __('QR') }}</span>
                            <span
                                class="text-sm font-bold text-indigo-600 dark:text-navy-50">{{ $qrRedeemedLast30Count ?? 0 }}</span>
                        </div>
                    </div>
                </x-slot>
            </x-provider.kpi-stat-card>
        </div>
    </div>

    {{-- 4. Quick Actions --}}
    <div class="mt-8">
        <div class="flex items-center gap-2 px-1 mb-3">
            <i class="fa-solid fa-rocket text-slate-400"></i>
            <h2 class="text-sm font-bold tracking-wide text-slate-600 dark:text-navy-100 uppercase">
                {{ __('provider.dashboard.quick_actions') }}
            </h2>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('provider.requests.index') }}"
                class="btn flex h-14 items-center justify-center gap-3 rounded-2xl bg-white border border-slate-100 dark:border-navy-500 shadow-sm hover:shadow-md transition-all group">
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                    <i class="fa-solid fa-inbox"></i>
                </span>
                <span class="font-bold text-slate-700 dark:text-navy-100">{{ __('Incoming Requests') }}</span>
            </a>
            <a href="{{ route('provider.menu-items.index') }}"
                class="btn flex h-14 items-center justify-center gap-3 rounded-2xl bg-white border border-slate-100 dark:border-navy-500 shadow-sm hover:shadow-md transition-all group">
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-success/10 text-success group-hover:bg-success group-hover:text-white transition-colors">
                    <i class="fa-solid fa-utensils"></i>
                </span>
                <span class="font-bold text-slate-700 dark:text-navy-100">{{ __('Inventory') }}</span>
            </a>
            <a href="{{ route('provider.qr.scan') }}"
                class="btn flex h-14 items-center justify-center gap-3 rounded-2xl bg-white border border-slate-100 dark:border-navy-500 shadow-sm hover:shadow-md transition-all group">
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-qrcode"></i>
                </span>
                <span class="font-bold text-slate-700 dark:text-navy-100">{{ __('Scan QR Code') }}</span>
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
                    data-chart-rtl="{{ app()->getLocale() === 'ar' ? '1' : '0' }}" x-data="{
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
            <div
                class="divide-y divide-slate-100 dark:divide-navy-500 rounded-lg border border-slate-100 dark:border-navy-500 overflow-hidden">
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
                                <span
                                    class="inline-flex w-fit shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-700 dark:bg-navy-600 dark:text-navy-100"
                                    dir="ltr">#{{ $req->id }}</span>
                                <span
                                    class="font-medium text-slate-700 dark:text-navy-100 break-words">{{ $recentType }}</span>
                            </div>
                            <div class="mt-1.5 flex w-full justify-start">
                                @include('provider.requests.partials.pseudonymous-ref-inline', ['ref' => $recentRef, 'variant' => 'compact'])
                            </div>
                            <p class="text-tiny text-slate-400 dark:text-navy-500 mt-1"
                                title="{{ $req->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}">
                                {{ __('provider.dashboard.request_created') }} {{ $req->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 self-start rounded-md bg-slate-50 px-2 py-1 text-xs font-medium leading-snug sm:max-w-[12rem] sm:text-end dark:bg-navy-600/80 {{ $req->status === 'FULFILLED' ? 'text-success' : ($req->status === 'REQUESTED' ? 'text-warning' : 'text-primary dark:text-accent-light') }}">
                            {{ $statusLabels[$req->status] ?? str_replace('_', ' ', $req->status) }}
                        </span>
                    </a>
                @empty
                    <div class="p-4 text-sm text-slate-500 dark:text-navy-400 text-center">
                        {{ __('provider.dashboard.no_requests') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>