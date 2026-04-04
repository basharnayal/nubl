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

    {{-- 3. KPI cards --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5 lg:gap-6">
        <div class="card justify-center p-4.5">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1 text-start">
                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100 tabular-nums" dir="ltr">{{ $pendingRequestsCount ?? 0 }}</p>
                    <p class="text-xs-plus line-clamp-2">{{ __('provider.dashboard.awaiting_response') }}</p>
                </div>
                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <a href="{{ route('provider.requests.index', ['status' => 'REQUESTED']) }}" class="badge mt-2 inline-flex bg-warning/10 py-1 px-1.5 text-warning dark:bg-warning/15 hover:bg-warning/20">
                {{ __('finance.common.view') }}
            </a>
        </div>
        <div class="card justify-center p-4.5">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1 text-start">
                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100 tabular-nums" dir="ltr">{{ $pendingProofCount ?? 0 }}</p>
                    <p class="text-xs-plus line-clamp-2">{{ __('Proof pending') }}</p>
                </div>
                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <a href="{{ route('provider.requests.index', ['needs_proof' => '1']) }}" class="badge mt-2 inline-flex bg-error/10 py-1 px-1.5 text-error dark:bg-error/15 hover:bg-error/20">
                {{ __('finance.common.view') }}
            </a>
        </div>
        <div class="card justify-center p-4.5">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1 text-start">
                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100 tabular-nums" dir="ltr">{{ $fulfilledLast30Count ?? 0 }}</p>
                    <p class="text-xs-plus line-clamp-2">{{ __('provider.dashboard.fulfilled_30d') }}</p>
                </div>
                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <a href="{{ route('provider.requests.index', ['status' => 'FULFILLED']) }}" class="badge mt-2 inline-flex bg-success/10 py-1 px-1.5 text-success dark:bg-success/15 hover:bg-success/20">
                {{ __('finance.common.view') }}
            </a>
        </div>
        <div class="card justify-center p-4.5 flex flex-col">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1 text-start">
                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100 tabular-nums" dir="ltr">{{ number_format($valueFulfilledLast30 ?? 0, 2) }}</p>
                    <p class="text-xs-plus line-clamp-2">{{ __('provider.dashboard.value_delivered_30d') }} ({{ __('SAR') }})</p>
                </div>
                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-primary dark:bg-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-slate-100/80 p-2.5 dark:bg-navy-600/50">
                <div class="text-center">
                    <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('provider.dashboard.in_pipeline') }}</p>
                    <p class="text-sm font-semibold tabular-nums text-slate-800 dark:text-navy-100" dir="ltr">{{ $inPipelineCount ?? 0 }}</p>
                </div>
                <div class="text-center border-s border-slate-200 dark:border-navy-500">
                    <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('provider.dashboard.qr_redeemed_30d') }}</p>
                    <p class="text-sm font-semibold tabular-nums text-slate-800 dark:text-navy-100" dir="ltr">{{ $qrRedeemedLast30Count ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Quick actions (prominent, full width) --}}
    <div class="card mt-4 px-4 pb-4 pt-1 sm:px-5">
        <div class="my-3">
            <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('provider.dashboard.quick_actions') }}</h2>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a href="{{ route('provider.requests.index') }}" class="btn flex w-full items-center justify-center gap-2 bg-primary py-2.5 text-white dark:bg-accent hover:bg-primary-focus dark:hover:bg-accent-focus">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <span>{{ __('Incoming Requests') }}</span>
            </a>
            <a href="{{ route('provider.menu-items.index') }}" class="btn flex w-full items-center justify-center gap-2 border-2 border-primary bg-transparent py-2.5 text-primary dark:border-accent dark:text-accent hover:bg-primary/10 dark:hover:bg-accent/10">
                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                <span>{{ __('Inventory') }}</span>
            </a>
            <a href="{{ route('provider.qr.scan') }}" class="btn flex w-full items-center justify-center gap-2 border border-slate-200 bg-white py-2.5 text-slate-800 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
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
            <div class="min-h-[250px] w-full overflow-x-auto" dir="ltr">
                <div class="ax-transparent-gridline min-w-[min(100%,42rem)] pe-2"
                     data-chart-series="{{ json_encode($weeklyFulfilledChart['series'] ?? []) }}"
                     data-chart-categories="{{ json_encode($weeklyFulfilledChart['categories'] ?? []) }}"
                     data-chart-label="{{ json_encode(__('provider.dashboard.chart_series')) }}"
                     data-chart-rtl="{{ app()->getLocale() === 'ar' ? '1' : '0' }}"
                     x-data="{
                        init() {
                            $nextTick(() => {
                                if (this.$el._x_chart) return;
                                const config = { ...pages.charts.incomePersonal };
                                const series = JSON.parse(this.$el.dataset.chartSeries);
                                const categories = JSON.parse(this.$el.dataset.chartCategories);
                                const label = JSON.parse(this.$el.dataset.chartLabel);
                                const isRtl = this.$el.dataset.chartRtl === '1';
                                config.series = [{ name: label, data: series }];
                                config.xaxis = { ...config.xaxis, categories, position: 'bottom' };
                                config.chart = { ...config.chart, toolbar: { show: false } };
                                config.yaxis = { ...config.yaxis, ...(isRtl ? { opposite: true } : {}) };
                                config.dataLabels = { ...config.dataLabels, enabled: series.some(v => v > 0) };
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
                    <a href="{{ route('provider.requests.show', $req->id) }}" class="flex flex-col gap-2 p-3 transition-colors hover:bg-slate-50 dark:hover:bg-navy-600/50 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0 flex-1 text-start">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-3">
                                <span class="inline-flex w-fit shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-700 dark:bg-navy-600 dark:text-navy-100" dir="ltr">#{{ $req->id }}</span>
                                <span class="font-medium text-slate-700 dark:text-navy-100 break-words">{{ $req->items->first()?->menuItem?->name ?? __('Request') }}</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-navy-400 mt-1">
                                <span class="text-slate-400 dark:text-navy-500">{{ __('Recipient') }}</span>
                                @if ($req->recipient?->name ?? $req->recipient?->email)
                                    <span class="ms-1">{{ $req->recipient->name ?? $req->recipient->email }}</span>
                                @else
                                    <span class="ms-1 italic text-slate-400">{{ __('provider.dashboard.no_recipient_name') }}</span>
                                @endif
                            </p>
                            <p class="text-tiny text-slate-400 dark:text-navy-500 mt-0.5" title="{{ $req->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}">
                                {{ __('provider.dashboard.request_created') }} {{ $req->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="shrink-0 self-start sm:self-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium leading-snug sm:max-w-[12rem] sm:text-end dark:bg-navy-600/80 {{ $req->status === 'FULFILLED' ? 'text-success' : ($req->status === 'REQUESTED' ? 'text-warning' : 'text-primary dark:text-accent-light') }}">
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
