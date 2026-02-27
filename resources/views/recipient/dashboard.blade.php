<x-app-layout title="{{ __('Recipient Dashboard') }}" is-header-blur="true">
    <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
            <div class="col-span-12 space-y-4 sm:space-y-5 lg:col-span-8 lg:space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5 lg:gap-6">
                    <div class="card relative overflow-hidden bg-linear-to-r from-blue-500 to-indigo-600 px-5 pb-5">
                        <div>
                            <div class="ax-transparent-gridline mt-5 w-1/2">
                                <div x-init="$nextTick(() => {
                                    $el._x_chart = new ApexCharts($el, pages.charts.earningWhite);
                                    $el._x_chart.render()
                                });"></div>
                            </div>
                            <p class="mt-3 text-base font-medium tracking-wide text-indigo-100">
                                {{ __('Remaining weekly limit') }}
                            </p>
                            <p class="mt-4 font-inter text-2xl font-semibold" dir="ltr">
                                <span class="text-indigo-100">{!! '&#x20C1;' !!}</span><span class="text-white">{{ number_format($remainingLimit ?? 0, 0) }}</span>
                            </p>
                            <div class="badge mt-2 rounded-full bg-black/20 text-indigo-50" dir="ltr">
                                {{ __('Weekly limit') }} {!! '&#x20C1;' !!}{{ $weeklyLimit ?? 400 }}
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                            <img class="w-24 translate-x-1/4 translate-y-1/4 opacity-50"
                                src="{{ asset('images/illustrations/the-dollar.svg') }}" alt="" />
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
                                <div class="badge mt-2 space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15">
                                    <span>{{ $pendingCount ?? 0 }}</span>
                                    <span class="text-tiny-plus">{{ __('pending') }}</span>
                                </div>
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
                                <a href="{{ route('recipient.requests.index') }}" class="badge mt-2 inline-flex space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15 hover:bg-success/20">
                                    <span>{{ __('View') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
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
                                <a href="{{ route('recipient.requests.index') }}" class="badge mt-2 inline-flex space-x-1 bg-warning/10 py-1 px-1.5 text-warning dark:bg-warning/15 hover:bg-warning/20">
                                    <span>{{ __('View') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- My Requests --}}
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm-plus font-medium tracking-wide text-slate-700 dark:text-navy-100">
                            {{ __('My Requests') }}
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
                            ];
                        @endphp
                        @forelse($recentRequests as $req)
                        <a href="{{ route('recipient.requests.show', $req->id) }}" class="card block p-3 hover:bg-slate-50 dark:hover:bg-navy-600/50 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ in_array($req->status, ['FULFILLED']) ? 'bg-success/10' : (in_array($req->status, ['REDEEMABLE', 'APPROVED']) ? 'bg-info/10' : 'bg-primary/10 dark:bg-accent/10') }}">
                                    <i class="fa-solid fa-utensils {{ in_array($req->status, ['FULFILLED']) ? 'text-success' : (in_array($req->status, ['REDEEMABLE', 'APPROVED']) ? 'text-info' : 'text-primary dark:text-accent-light') }}"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-slate-700 dark:text-navy-100">{{ $req->items->first()?->menuItem?->name ?? __('Request') }}</p>
                                    <div class="mt-0.5 flex text-xs text-slate-400 dark:text-navy-300">
                                        <p>{{ __('Provider:') }} {{ $req->provider->providerProfile->full_name_en ?? $req->provider->providerProfile->full_name_ar ?? $req->provider->name }}</p>
                                        <div class="mx-2 my-1 hidden w-px bg-slate-200 dark:bg-navy-500 sm:flex"></div>
                                        <p class="hidden sm:flex">{{ __('Requested:') }} {{ $req->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="-mt-3 text-right text-xs font-medium {{ $req->status === 'FULFILLED' ? 'text-success' : (in_array($req->status, ['REDEEMABLE', 'APPROVED']) ? 'text-info' : 'text-primary dark:text-accent-light') }}">{{ $statusLabels[$req->status] ?? str_replace('_', ' ', $req->status) }}</p>
                            <div class="progress mt-2 h-1.5 bg-slate-150 dark:bg-navy-500">
                                <div class="relative overflow-hidden rounded-full {{ $req->status === 'FULFILLED' ? 'w-full bg-success' : (in_array($req->status, ['REDEEMABLE', 'APPROVED']) ? 'w-3/4 bg-info' : 'w-3/12 bg-primary dark:bg-accent') }}"></div>
                            </div>
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
                            <a href="{{ route('recipient.requests.show', $activityReq->id) }}" class="flex cursor-pointer items-center justify-between hover:bg-slate-50 dark:hover:bg-navy-600/50 rounded-lg -mx-2 px-2 py-2 transition-colors">
                                <div class="flex items-center space-x-3.5">
                                    <div class="avatar size-10">
                                        <div class="is-initial rounded-full bg-primary/10 text-sm-plus font-medium text-primary dark:bg-accent/10 dark:text-accent-light">{{ substr($activityReq->provider->providerProfile->full_name_en ?? $activityReq->provider->name ?? '??', 0, 2) }}</div>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-navy-100">{{ $activityReq->provider->providerProfile->full_name_en ?? $activityReq->provider->providerProfile->full_name_ar ?? $activityReq->provider->name }}</p>
                                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ $activityReq->created_at->diffForHumans() }} — {{ $statusLabels[$activityReq->status] ?? $activityReq->status }}</p>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <a href="{{ route('recipient.providers.show', $provider->id) }}" class="flex cursor-pointer items-center justify-between space-x-2 hover:bg-slate-50 dark:hover:bg-navy-600/50 rounded-lg -mx-2 px-2 py-2 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="avatar size-10">
                                        <div class="is-initial rounded-full bg-secondary/10 text-sm-plus font-medium text-secondary">{{ substr($provider->providerProfile->full_name_en ?? $provider->providerProfile->full_name_ar ?? $provider->name ?? '??', 0, 2) }}</div>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-navy-100">{{ $provider->providerProfile->full_name_en ?? $provider->providerProfile->full_name_ar ?? $provider->name }}</p>
                                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('View menu') }}</p>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <div class="card px-4 pb-4 sm:px-5">
                        <div class="my-3 flex h-8 items-center justify-between">
                            <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                                {{ __('Quick Actions') }}
                            </h2>
                        </div>
                        <div class="space-y-3">
                            <a href="{{ route('recipient.providers.index') }}" class="btn flex w-full items-center justify-center space-x-2 bg-primary py-2.5 text-white dark:bg-accent hover:bg-primary-focus dark:hover:bg-accent-focus">
                                <i class="fa-solid fa-utensils"></i>
                                <span>{{ __('Browse Providers') }}</span>
                            </a>
                            <a href="{{ route('recipient.providers.index') }}" class="btn flex w-full items-center justify-center space-x-2 border-2 border-primary bg-transparent py-2.5 text-primary dark:border-accent dark:text-accent hover:bg-primary/10 dark:hover:bg-accent/10">
                                <i class="fa-solid fa-plus"></i>
                                <span>{{ __('New Request') }}</span>
                            </a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="mt-3 flex h-8 items-center justify-between px-4 sm:px-5">
                            <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                                {{ __('Activity Overview') }}
                            </h2>
                        </div>
                        <div class="ax-transparent-gridline pr-2">
                            <div x-init="$nextTick(() => {
                                $el._x_chart = new ApexCharts($el, pages.charts.incomePersonal);
                                $el._x_chart.render()
                            });"></div>
                        </div>
                    </div>
                    <div class="card p-4">
                        <div class="space-y-1 text-center font-inter text-xs-plus">
                            <div class="flex items-center justify-between px-2 pb-4">
                                <p class="font-medium text-slate-700 dark:text-navy-100">{{ now()->translatedFormat('F Y') }}</p>
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
                                    <p class="text-slate-500 dark:text-navy-400">{{ __('Al-Rashid Kitchen is now available') }}</p>
                                </div>
                                <div class="rounded-lg bg-success/5 px-3 py-2 text-xs">
                                    <p class="font-medium text-slate-700 dark:text-navy-100">{{ __('Community support') }}</p>
                                    <p class="text-slate-500 dark:text-navy-400">{{ __('Weekly meal program expanded') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
