{{--
    KPI GRID — Four compact metric cards (2 × 2).
    Uses translation keys from DashboardService::buildKpis().
--}}
@php
    $colorMap = [
        'amber'  => [
            'icon'  => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            'value' => 'text-amber-700 dark:text-amber-300',
            'link'  => 'text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300',
        ],
        'blue'   => [
            'icon'  => 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light',
            'value' => 'text-primary dark:text-accent-light',
            'link'  => 'text-primary hover:text-primary/80 dark:text-accent-light dark:hover:text-accent',
        ],
        'green'  => [
            'icon'  => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
            'value' => 'text-emerald-700 dark:text-emerald-300',
            'link'  => 'text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300',
        ],
        'purple' => [
            'icon'  => 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400',
            'value' => 'text-purple-700 dark:text-purple-300',
            'link'  => 'text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300',
        ],
    ];
@endphp

<div class="grid h-full grid-cols-2 gap-3">
    @foreach ($kpis as $kpi)
        @php $c = $colorMap[$kpi['color']] ?? $colorMap['blue']; @endphp

        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">

            {{-- Icon --}}
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $c['icon'] }}">
                <i class="{{ $kpi['icon'] }} text-sm" aria-hidden="true"></i>
            </div>

            {{-- Value --}}
            <div class="mt-3 flex-1">
                <p class="text-2xl font-bold tabular-nums tracking-tight {{ $c['value'] }}">
                    @if ($kpi['value_format'] === 'currency')
                        <x-sar-amount :value="number_format((float) $kpi['value'], 2)" />
                    @else
                        {{ number_format((int) $kpi['value']) }}
                    @endif
                </p>
                <p class="mt-0.5 text-sm font-medium text-slate-700 dark:text-navy-100">
                    {{ __($kpi['label_key']) }}
                </p>
                <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-400 dark:text-navy-400">
                    {{ __($kpi['sub_key'], $kpi['sub_params']) }}
                </p>
            </div>

            {{-- Link --}}
            @if (! empty($kpi['route']))
                <a href="{{ route($kpi['route']) }}"
                   class="mt-3 text-xs font-semibold transition-colors {{ $c['link'] }}">
                    {{ __($kpi['action_key']) }} →
                </a>
            @endif

        </div>
    @endforeach
</div>
