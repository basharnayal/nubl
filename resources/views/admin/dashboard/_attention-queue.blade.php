{{--
    ATTENTION QUEUE — Severity-sorted action items.
    High → Medium · Each row has a left border accent, icon, label, count, description, action link.
    Empty state shown when nothing needs attention.
--}}
@php
    $highCount = collect($items)->where('severity', 'high')->count();

    $borderClasses = [
        'high'   => 'border-s-rose-400 dark:border-s-rose-500',
        'medium' => 'border-s-amber-400 dark:border-s-amber-500',
        'low'    => 'border-s-slate-300 dark:border-s-navy-500',
    ];
    $iconClasses = [
        'high'   => 'text-rose-500 dark:text-rose-400',
        'medium' => 'text-amber-500 dark:text-amber-400',
        'low'    => 'text-slate-400 dark:text-navy-400',
    ];
    $countBadgeClasses = [
        'high'   => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
        'medium' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'low'    => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-300',
    ];
    $severityLabelClasses = [
        'high'   => 'text-rose-600 dark:text-rose-400',
        'medium' => 'text-amber-600 dark:text-amber-400',
        'low'    => 'text-slate-400 dark:text-navy-400',
    ];
@endphp

<section aria-labelledby="attention-heading"
         class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-navy-700">
        <div class="flex items-center gap-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg
                {{ $highCount > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' : 'bg-slate-100 text-slate-500 dark:bg-navy-700 dark:text-navy-300' }}">
                <i class="fa-solid fa-bell text-sm" aria-hidden="true"></i>
            </div>
            <div>
                <h2 id="attention-heading" class="font-semibold text-slate-800 dark:text-navy-50">
                    {{ __('dashboard.attention.title') }}
                </h2>
                <p class="text-xs text-slate-400 dark:text-navy-400">
                    {{ __('dashboard.attention.subtitle') }}
                </p>
            </div>
        </div>

        @if (count($items) > 0)
            <div class="flex items-center gap-2">
                @if ($highCount > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse" aria-hidden="true"></span>
                        {{ __('dashboard.attention.high_count', ['count' => $highCount]) }}
                    </span>
                @endif
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-navy-700 dark:text-navy-300">
                    {{ __('dashboard.attention.total_count', ['count' => count($items)]) }}
                </span>
            </div>
        @endif
    </div>

    {{-- Body --}}
    @if (count($items) === 0)

        {{-- Empty state --}}
        <div class="flex flex-1 flex-col items-center justify-center gap-4 px-5 py-14 text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-500/10">
                <i class="fa-solid fa-circle-check text-3xl text-emerald-500 dark:text-emerald-400" aria-hidden="true"></i>
            </div>
            <div>
                <p class="font-semibold text-slate-800 dark:text-navy-50">
                    {{ __('dashboard.attention.all_clear') }}
                </p>
                <p class="mt-1 max-w-[22rem] text-sm text-slate-400 dark:text-navy-400">
                    {{ __('dashboard.attention.all_clear_sub') }}
                </p>
            </div>
        </div>

    @else

        <ul role="list" class="divide-y divide-slate-100 dark:divide-navy-700">
            @foreach ($items as $item)
                @php
                    $border  = $borderClasses[$item['severity']] ?? $borderClasses['low'];
                    $icon    = $iconClasses[$item['severity']] ?? $iconClasses['low'];
                    $badge   = $countBadgeClasses[$item['severity']] ?? $countBadgeClasses['low'];
                    $sevLbl  = $severityLabelClasses[$item['severity']] ?? $severityLabelClasses['low'];
                @endphp

                <li class="flex items-start gap-4 border-s-[3px] px-5 py-4 transition-colors hover:bg-slate-50/60 dark:hover:bg-navy-750/30 {{ $border }}">

                    {{-- Severity icon --}}
                    <div class="mt-0.5 w-4 shrink-0 text-center {{ $icon }}">
                        <i class="{{ $item['icon'] }} text-base" aria-hidden="true"></i>
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="font-medium text-slate-800 dark:text-navy-100">
                                {{ __($item['labelKey']) }}
                            </span>

                            @if ($item['count'] !== null)
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold tabular-nums {{ $badge }}">
                                    {{ number_format($item['count']) }}
                                </span>
                            @endif

                            <span class="ms-auto text-[10px] font-bold uppercase tracking-widest {{ $sevLbl }}">
                                {{ __('dashboard.attention.severity.' . $item['severity']) }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-sm text-slate-500 dark:text-navy-300">
                            {{ __($item['descKey'], $item['descParams']) }}
                        </p>
                    </div>

                    {{-- Action link --}}
                    @if (! empty($item['actionRoute']) && ! empty($item['actionKey']))
                        <a href="{{ route($item['actionRoute']) }}"
                           class="shrink-0 self-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-200 dark:hover:border-navy-500 dark:hover:bg-navy-600">
                            {{ __($item['actionKey']) }} →
                        </a>
                    @endif

                </li>
            @endforeach
        </ul>

    @endif
</section>
