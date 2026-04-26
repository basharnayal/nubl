<x-app-layout title="{{ __('Recipient Dashboard') }}" is-header-blur="true">
    <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
            <div class="col-span-12 space-y-4 sm:space-y-5 lg:col-span-8 lg:space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5 lg:gap-6">
                    <div class="card relative overflow-hidden bg-linear-to-r from-blue-500 to-indigo-600 px-5 pb-5">
                        <div>
                            <div class="ax-transparent-gridline mt-5 w-1/2">
                                <div x-init="$nextTick(async () => {
                                    const ApexCharts = await window.loadApexCharts();
                                    $el._x_chart = new ApexCharts($el, pages.charts.earningWhite);
                                    $el._x_chart.render()
                                });"></div>
                            </div>
                            <p class="mt-3 text-base font-medium tracking-wide text-indigo-100">
                                {{ __('Remaining weekly limit') }}
                            </p>
                            <p class="mt-4 font-inter text-2xl font-semibold" dir="ltr">
                                <span class="text-indigo-100"><x-sar-symbol /></span><span class="text-white">{{ number_format($remainingLimit ?? 0, 0) }}</span>
                            </p>
                            <div class="badge mt-2 rounded-full bg-black/20 text-indigo-50" dir="ltr">
                                {{ __('Weekly limit') }} <x-sar-symbol /> {{ $weeklyLimit ?? 400 }}
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                            <div class="translate-x-2 translate-y-2 select-none font-inter text-7xl font-black text-white/20" aria-hidden="true">
                                <x-sar-symbol />
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2 sm:gap-5 lg:gap-6">
                        <div class="card justify-center p-4.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100">{{ $activeRequestsCount ?? 0 }}</p>
                                    <p class="text-xs-plus line-clamp-1">{{ __('Active Requests') }}</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-primary dark:bg-accent">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a
                                    href="{{ route('recipient.requests.index', ['status' => 'redeemable']) }}"
                                    class="badge mt-2 inline-flex space-x-1 bg-amber-100 py-1 px-1.5 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:hover:bg-amber-500/25"
                                >
                                    <span>{{ __('View') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                        <div class="card justify-center p-4.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100">{{ $completedOrdersCount ?? 0 }}</p>
                                    <p class="text-xs-plus line-clamp-1">{{ __('Completed Orders') }}</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('recipient.requests.index', ['status' => 'fulfilled']) }}" class="badge mt-2 inline-flex space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15 hover:bg-success/20">
                                    <span>{{ __('View') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                        <div class="card justify-center p-4.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100">{{ $providersCount ?? 0 }}</p>
                                    <p class="text-xs-plus line-clamp-1">{{ __('Providers') }}</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('recipient.providers.index') }}" class="badge mt-2 inline-flex space-x-1 bg-info/10 py-1 px-1.5 text-info dark:bg-info/15 hover:bg-info/20">
                                    <span>{{ __('Browse') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                        <div class="card justify-center p-4.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100">{{ $pendingCount ?? 0 }}</p>
                                    <p class="text-xs-plus line-clamp-1">{{ __('Pending') }}</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-warning">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('recipient.requests.index', ['status' => 'pending']) }}" class="badge mt-2 inline-flex space-x-1 bg-warning/10 py-1 px-1.5 text-warning dark:bg-warning/15 hover:bg-warning/20">
                                    <span>{{ __('View') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Pending & Redeemable --}}
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm-plus font-medium tracking-wide text-slate-700 dark:text-navy-100">
                            {{ __('Pending & Redeemable') }}
                        </h2>
                        <a href="{{ route('recipient.requests.index') }}"
                            class="border-b border-dotted border-current pb-0.5 text-xs-plus font-medium text-primary outline-hidden transition-colors duration-300 hover:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70">
                            {{ __('View All') }}
                        </a>
                    </div>
                    <div class="mt-3 space-y-3.5">
                        @php
                            $statusLabels = [
                                'REQUESTED' => __('Requested'),
                                'APPROVED' => __('Approved'), // Provider adopted (PROVIDER_ADOPTION)
                                'ADMIN_PENDING' => __('Admin Pending'),
                                'ADMIN_APPROVED' => __('Admin Approved'),
                                'REDEEMABLE' => __('Redeemable'),
                                'FULFILLED' => __('Fulfilled'),
                                'REJECTED' => __('Rejected'),
                                'ADMIN_REJECTED' => __('Rejected'),
                                'CANCELLED' => __('Cancelled'),
                                'CANCELED' => __('Cancelled'),
                            ];
                        @endphp
                        @forelse(($dashboardMyRequests ?? collect()) as $req)
                        @php
                            $reqProvider = $req->provider;
                            $reqProviderTitle = \App\Support\ProviderDisplay::businessTitle($reqProvider->providerProfile, $reqProvider->name);

                            $statusCard = \App\Support\RecipientRequestStatusPresenter::card($req, false);
                            $steps = array_slice($statusCard['steps'] ?? [], 0, 4);
                            $doneCount = collect($steps)->filter(fn ($s) => ($s['state'] ?? 'pending') === 'done')->count();
                            $currentIndex = collect($steps)->search(fn ($s) => ($s['state'] ?? 'pending') === 'current');
                            $currentIndex = $currentIndex === false ? null : (int) $currentIndex;
                            $totalSteps = max(1, count($steps));
                            $progressIndex = $currentIndex ?? $doneCount;
                            $progressPct = $totalSteps <= 1 ? 0 : (int) round(($progressIndex / ($totalSteps - 1)) * 100);

                            $barColorClass = match ($req->status) {
                                'FULFILLED' => 'bg-success',
                                'REDEEMABLE', 'APPROVED', 'ADMIN_APPROVED' => 'bg-info',
                                'CANCELLED', 'CANCELED', 'REJECTED', 'ADMIN_REJECTED' => 'bg-amber-500',
                                default => 'bg-amber-500',
                            };

                            $statusTextClass = match ($req->status) {
                                'FULFILLED' => 'text-success',
                                'REDEEMABLE', 'APPROVED', 'ADMIN_APPROVED' => 'text-info',
                                'CANCELLED', 'CANCELED', 'REJECTED', 'ADMIN_REJECTED' => 'text-amber-600 dark:text-amber-400',
                                default => 'text-amber-600 dark:text-amber-400',
                            };

                            $iconBgClass = match ($req->status) {
                                'FULFILLED' => 'bg-success/10',
                                'REDEEMABLE', 'APPROVED', 'ADMIN_APPROVED' => 'bg-info/10',
                                default => 'bg-amber-100 dark:bg-amber-500/15',
                            };

                            $iconTextClass = match ($req->status) {
                                'FULFILLED' => 'text-success',
                                'REDEEMABLE', 'APPROVED', 'ADMIN_APPROVED' => 'text-info',
                                default => 'text-amber-600 dark:text-amber-300',
                            };
                        @endphp
                        <a href="{{ route('recipient.requests.show', $req->id) }}" class="card block p-3 hover:bg-slate-50 dark:hover:bg-navy-600/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="avatar size-10 shrink-0">
                                    <x-provider-profile-avatar :profile="$reqProvider->providerProfile" :title="$reqProviderTitle" variant="compact-primary" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-slate-700 dark:text-navy-100">{{ $req->items->first()?->menuItem?->name ?? __('Request') }}</p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-400 dark:text-navy-300">
                                        <span>{{ __('Provider:') }} {{ $reqProviderTitle }}</span>
                                        <span class="hidden h-3 w-px shrink-0 bg-slate-200 dark:bg-navy-500 sm:inline-block" aria-hidden="true"></span>
                                        <span class="hidden sm:inline">{{ __('Requested:') }} {{ $req->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <p class="max-w-[42%] shrink-0 text-end text-xs leading-snug font-medium sm:max-w-[36%] {{ $statusTextClass }}">{{ $statusLabels[$req->status] ?? str_replace('_', ' ', $req->status) }}</p>
                            </div>
                            @php
                                // Render the same "connector line" logic as the hero (solid when left is done and right is done/current).
                                $lineStatic = in_array($req->status, ['FULFILLED', 'CANCELLED', 'CANCELED'], true);
                                $solidShimmerClass = $lineStatic ? '' : 'animate-[recipient-step-line-shimmer_1.65s_linear_infinite]';
                                $dashFlowClass = $lineStatic ? '' : 'animate-[recipient-step-dash-flow_0.95s_linear_infinite]';
                                $isCancelled = in_array($req->status, ['CANCELLED', 'CANCELED'], true);
                                $segSolid = [];
                                for ($i = 1; $i < count($steps); $i++) {
                                    $left = $steps[$i - 1]['state'] ?? 'pending';
                                    $right = $steps[$i]['state'] ?? 'pending';
                                    $segSolid[$i] = $left === 'done' && in_array($right, ['done', 'current'], true);
                                }
                            @endphp
                            @if($isCancelled)
                                <div class="mt-2 flex items-center">
                                    <div class="flex size-6 items-center justify-center rounded-full bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M18.364 5.636 5.636 18.364M5.636 5.636l12.728 12.728" />
                                        </svg>
                                    </div>
                                </div>
                            @else
                                <div class="mt-2 grid grid-cols-3 gap-x-2">
                                    @for($i = 1; $i < 4; $i++)
                                        @if(($segSolid[$i] ?? false) === true)
                                            <div class="relative h-1 w-full overflow-hidden rounded-full shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $barColorClass }}">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 w-[min(72%,11rem)] -translate-x-full bg-gradient-to-r from-transparent via-white/95 to-transparent motion-reduce:animate-none dark:via-white/50 dark:from-transparent dark:to-transparent {{ $solidShimmerClass }} [mask-image:linear-gradient(90deg,transparent_0%,black_18%,black_82%,transparent_100%)]"></span>
                                            </div>
                                        @else
                                            <div class="h-1 w-full rounded-full bg-[repeating-linear-gradient(90deg,rgb(148_163_184)_0px,rgb(148_163_184)_6px,transparent_6px,transparent_14px)] bg-[length:24px_100%] shadow-[inset_0_1px_0_rgba(255,255,255,0.25)] motion-reduce:animate-none {{ $dashFlowClass }} dark:bg-[repeating-linear-gradient(90deg,rgb(173_181_189)_0px,rgb(173_181_189)_6px,transparent_6px,transparent_14px)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"></div>
                                        @endif
                                    @endfor
                                </div>
                            @endif
                        </a>
                        @empty
                        <div class="card p-3 text-center text-slate-500 dark:text-navy-400">
                            <p>{{ __('No requests yet.') }}</p>
                            <a href="{{ route('recipient.providers.index') }}" class="mt-2 inline-block text-primary dark:text-accent-light hover:underline">{{ __('Browse providers') }}</a>
                        </div>
                        @endforelse
                    </div>
                </div>
                {{-- Recent Activity & Providers --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:gap-6">
                    <div class="card px-4 pb-4 sm:px-5">
                        <div class="my-3 flex h-8 items-center justify-between">
                            <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                                {{ __('Recent Activity') }}
                            </h2>
                            <a href="{{ route('recipient.requests.index') }}"
                                class="border-b border-dotted border-current pb-0.5 text-xs-plus font-medium text-primary outline-hidden transition-colors duration-300 hover:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70">
                                {{ __('View All') }}
                            </a>
                        </div>
                        <div class="space-y-3.5">
                            @forelse($recentRequests as $activityReq)
                            @php
                                $actP = $activityReq->provider;
                                $actProviderTitle = \App\Support\ProviderDisplay::businessTitle($actP->providerProfile, $actP->name);
                            @endphp
                            <a href="{{ route('recipient.requests.show', $activityReq->id) }}" class="flex cursor-pointer items-center justify-between hover:bg-slate-50 dark:hover:bg-navy-600/50 rounded-lg -mx-2 px-2 py-2 transition-colors">
                                <div class="flex items-center space-x-3.5">
                                    <div class="avatar size-10">
                                        <x-provider-profile-avatar :profile="$actP->providerProfile" :title="$actProviderTitle" variant="compact-primary" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-navy-100">{{ $actProviderTitle }}</p>
                                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ $activityReq->created_at->diffForHumans() }} — {{ $statusLabels[$activityReq->status] ?? $activityReq->status }}</p>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-400 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            @empty
                            <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('No recent activity.') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="card px-4 pb-4 sm:px-5">
                        <div class="my-3 flex h-8 items-center justify-between">
                            <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                                {{ __('Browse Providers') }}
                            </h2>
                            <a href="{{ route('recipient.providers.index') }}"
                                class="border-b border-dotted border-current pb-0.5 text-xs-plus font-medium text-primary outline-hidden transition-colors duration-300 hover:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70">
                                {{ __('View All') }}
                            </a>
                        </div>
                        <div class="space-y-3.5">
                            @forelse($providers ?? [] as $provider)
                            @php
                                $browseTitle = \App\Support\ProviderDisplay::businessTitle($provider->providerProfile, $provider->name);
                            @endphp
                            <a href="{{ route('recipient.providers.show', $provider->id) }}" class="flex cursor-pointer items-center justify-between space-x-2 hover:bg-slate-50 dark:hover:bg-navy-600/50 rounded-lg -mx-2 px-2 py-2 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="avatar size-10">
                                        <x-provider-profile-avatar :profile="$provider->providerProfile" :title="$browseTitle" variant="compact-secondary" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-navy-100">{{ $browseTitle }}</p>
                                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('View menu') }}</p>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            @empty
                            <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('No providers available.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            {{-- Sidebar --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
                    <div class="card">
                        <div class="mt-3 flex h-8 items-center justify-between px-4 sm:px-5">
                            <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                                {{ __('Activity Overview') }}
                            </h2>
                        </div>
                        <div class="ax-transparent-gridline pr-2 min-h-[250px]"
                             data-chart-series="{{ json_encode($activityChartData['series'] ?? []) }}"
                             data-chart-categories="{{ json_encode($activityChartData['categories'] ?? []) }}"
                             data-chart-label="{{ json_encode(__('Amount Spent')) }}"
                             x-data="{
                                async init() {
                                    await $nextTick();
                                    if (this.$el._x_chart) return;
                                    const ApexCharts = await window.loadApexCharts();
                                    const config = { ...pages.charts.incomePersonal };
                                    const series = JSON.parse(this.$el.dataset.chartSeries);
                                    const categories = JSON.parse(this.$el.dataset.chartCategories);
                                    const label = JSON.parse(this.$el.dataset.chartLabel);
                                    config.series = [{ name: label, data: series }];
                                    config.xaxis = { ...config.xaxis, categories };
                                    if (series.every(v => v === 0)) {
                                        config.yaxis = { ...config.yaxis, min: 0, max: 5, forceNiceScale: true };
                                    }
                                    this.$el._x_chart = new ApexCharts(this.$el, config);
                                    this.$el._x_chart.render();
                                }
                             }"></div>
                    </div>
                    <div class="card p-4">
                        <div class="space-y-1 text-center font-inter text-xs-plus">
                            <div class="flex items-center justify-between px-2 pb-4">
                                <p class="font-medium text-slate-700 dark:text-navy-100">{{ now()->locale(app()->getLocale())->translatedFormat('F Y') }}</p>
                                <div class="-mr-1.5 flex space-x-2">
                                    <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 pb-2">
                                @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $d)
                                <div class="text-tiny-plus font-semibold text-primary dark:text-accent-light">{{ __($d) }}</div>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-7 place-items-center">
                                @php $today = (int)now()->format('d'); @endphp
                                @foreach(range(1, 31) as $day)
                                <button class="flex h-7 w-9 items-center justify-center rounded-xl text-slate-900 hover:bg-primary/10 hover:text-primary dark:text-navy-100 dark:hover:bg-accent-light/10 dark:hover:text-accent-light {{ $day === $today ? 'font-medium text-primary dark:text-accent-light bg-primary/10 dark:bg-accent-light/10' : '' }}">
                                    {{ $day }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                                    {{ __('NUBL Updates') }}
                                </h2>
                                <i class="fa-solid fa-bell text-primary dark:text-accent-light"></i>
                            </div>
                            <p class="mt-3 text-xs-plus text-slate-600 dark:text-navy-300">
                                {{ __('Stay updated with new providers and community support initiatives in your area.') }}
                            </p>
                            <div class="mt-3 space-y-2">
                                <div class="rounded-lg bg-primary/5 dark:bg-accent/5 px-3 py-2 text-xs">
                                    <p class="font-medium text-slate-700 dark:text-navy-100">{{ __('New provider joined') }}</p>
                                    <p class="text-slate-500 dark:text-navy-400">
                                        @if(!empty($latestProvider))
                                            @php
                                                $latestProviderTitle = \App\Support\ProviderDisplay::businessTitle($latestProvider->providerProfile, $latestProvider->name);
                                            @endphp
                                            <a href="{{ route('recipient.providers.show', $latestProvider->id) }}" class="font-medium text-primary hover:underline dark:text-accent-light">
                                                {{ $latestProviderTitle }}
                                            </a>
                                            <span>— {{ __('recipient.dashboard.promo_new_provider_body', ['provider' => $latestProviderTitle]) }}</span>
                                        @else
                                            {{ __('recipient.dashboard.promo_new_provider_body_fallback') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="rounded-lg bg-success/5 px-3 py-2 text-xs">
                                    <p class="font-medium text-slate-700 dark:text-navy-100">{{ __('Community support') }}</p>
                                    <p class="text-slate-500 dark:text-navy-400">
                                        {{ __('recipient.dashboard.promo_community_body', ['count' => $communityFulfilledThisWeek ?? 0]) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
