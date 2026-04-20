{{--
    STATUS STRIP — Five operational status pills, always on one row.
    Badge text is removed from visible display and moved to the tooltip (title attribute).
    Pill shows: severity dot · icon · label only.
--}}
@php
    $pillClasses = [
        'ok'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200/80 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20 dark:hover:bg-emerald-500/15',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200/80 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20 dark:hover:bg-amber-500/15',
        'high'    => 'bg-rose-50 text-rose-700 ring-rose-200/80 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20 dark:hover:bg-rose-500/15',
    ];
    $dotClasses = [
        'ok'      => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'high'    => 'bg-rose-500 animate-pulse',
    ];
@endphp

<section aria-label="{{ __('dashboard.title') }}">
    {{--
        flex-nowrap guarantees a single row.
        overflow-x-auto adds horizontal scroll on very small screens.
        gap-2 keeps pills tight enough to fit on 1024px+ without scrolling.
    --}}
    <div class="flex flex-nowrap gap-2 overflow-x-auto pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach ($statuses as $status)
            @php
                $pill   = $pillClasses[$status['severity']] ?? $pillClasses['ok'];
                $dot    = $dotClasses[$status['severity']] ?? $dotClasses['ok'];
                $tooltip = __($status['tooltip_key'], $status['tooltip_params']);
                $isLink  = ! empty($status['route']);
            @endphp

            @if ($isLink)
                <a href="{{ route($status['route']) }}"
                   title="{{ $tooltip }}"
                   class="group flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium ring-1 ring-inset transition-all duration-150 {{ $pill }}">
            @else
                <div title="{{ $tooltip }}"
                     class="flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium ring-1 ring-inset {{ $pill }}">
            @endif

                {{-- Severity dot --}}
                <span class="h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>

                {{-- Icon --}}
                <i class="{{ $status['icon'] }} text-xs" aria-hidden="true"></i>

                {{-- Label --}}
                <span class="whitespace-nowrap">{{ __($status['label_key']) }}</span>

                {{-- Subtle arrow for linked pills --}}
                @if ($isLink)
                    <i class="fa-solid fa-arrow-up-right-from-square ms-0.5 text-[9px] opacity-0 transition-opacity group-hover:opacity-50"
                       aria-hidden="true"></i>
                @endif

            @if ($isLink)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
</section>
