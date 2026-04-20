{{--
    PLATFORM SNAPSHOT — Privacy-safe aggregate platform statistics.
    No PII — aggregate counts only.
--}}
@php
    $fulfillmentRate = $platform['requests_30d'] > 0
        ? round(($platform['fulfilled_30d'] / $platform['requests_30d']) * 100)
        : 0;

    $stats = [
        [
            'label_key' => 'dashboard.platform.total_users',
            'value'     => $platform['total_users'],
            'icon'      => 'fa-solid fa-users',
            'color'     => 'text-primary dark:text-accent-light',
            'bg'        => 'bg-primary/10 dark:bg-accent/15',
        ],
        [
            'label_key' => 'dashboard.platform.donors',
            'value'     => $platform['donors'],
            'icon'      => 'fa-solid fa-heart',
            'color'     => 'text-rose-600 dark:text-rose-400',
            'bg'        => 'bg-rose-50 dark:bg-rose-500/10',
        ],
        [
            'label_key' => 'dashboard.platform.recipients',
            'value'     => $platform['recipients'],
            'icon'      => 'fa-solid fa-person',
            'color'     => 'text-blue-600 dark:text-blue-400',
            'bg'        => 'bg-blue-50 dark:bg-blue-500/10',
        ],
        [
            'label_key' => 'dashboard.platform.providers',
            'value'     => $platform['providers'],
            'icon'      => 'fa-solid fa-store',
            'color'     => 'text-purple-600 dark:text-purple-400',
            'bg'        => 'bg-purple-50 dark:bg-purple-500/10',
        ],
        [
            'label_key' => 'dashboard.platform.approved_providers',
            'value'     => $platform['approved_providers'],
            'icon'      => 'fa-solid fa-circle-check',
            'color'     => 'text-emerald-600 dark:text-emerald-400',
            'bg'        => 'bg-emerald-50 dark:bg-emerald-500/10',
        ],
        [
            'label_key' => 'dashboard.platform.pending_accounts',
            'value'     => $platform['pending_users'],
            'icon'      => 'fa-solid fa-user-clock',
            'color'     => 'text-amber-600 dark:text-amber-400',
            'bg'        => 'bg-amber-50 dark:bg-amber-500/10',
        ],
    ];
@endphp

<section aria-labelledby="platform-heading"
         class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">

    {{-- Header --}}
    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 dark:border-navy-700">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
            <i class="fa-solid fa-chart-pie text-sm" aria-hidden="true"></i>
        </div>
        <div>
            <h2 id="platform-heading" class="font-semibold text-slate-800 dark:text-navy-50">
                {{ __('dashboard.platform.title') }}
            </h2>
            <p class="text-xs text-slate-400 dark:text-navy-400">
                {{ __('dashboard.platform.subtitle') }}
            </p>
        </div>
    </div>

    {{-- User & provider stats (3×2 grid) --}}
    <div class="grid grid-cols-3 divide-x divide-y divide-slate-100 dark:divide-navy-700">
        @foreach ($stats as $stat)
            <div class="flex items-center gap-3 px-4 py-3.5">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $stat['bg'] }} {{ $stat['color'] }}">
                    <i class="{{ $stat['icon'] }} text-xs" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[10px] font-medium leading-tight text-slate-500 dark:text-navy-400">
                        {{ __($stat['label_key']) }}
                    </p>
                    <p class="text-base font-bold tabular-nums text-slate-900 dark:text-navy-50">
                        {{ number_format($stat['value']) }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 30-day separator --}}
    <div class="border-t border-slate-100 px-5 py-3 dark:border-navy-700">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-navy-500">
            {{ __('dashboard.platform.last_30_days') }}
        </p>
    </div>

    {{-- 30-day stats --}}
    <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-navy-700 pb-1">

        <div class="flex items-center gap-3 px-5 pb-4">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                <i class="fa-solid fa-inbox text-sm" aria-hidden="true"></i>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-slate-900 dark:text-navy-50">
                    {{ number_format($platform['requests_30d']) }}
                </p>
                <p class="text-xs text-slate-500 dark:text-navy-300">{{ __('dashboard.platform.requests_submitted') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 pb-4">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-slate-900 dark:text-navy-50">
                    {{ number_format($platform['fulfilled_30d']) }}
                    @if ($fulfillmentRate > 0)
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $fulfillmentRate }}%
                        </span>
                    @endif
                </p>
                <p class="text-xs text-slate-500 dark:text-navy-300">{{ __('dashboard.platform.requests_fulfilled') }}</p>
            </div>
        </div>

    </div>
</section>
