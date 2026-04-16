@props([
    'iconBgClass' => 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light',
    'valueWrapClass' => '',
])

@php
    $kpiIconWrap = 'flex size-11 shrink-0 items-center justify-center rounded-xl';
@endphp

<div {{ $attributes->class(['card flex h-full min-h-0 flex-col p-5']) }}>
    {{-- Zone 1: value (badge left) + icon (right) --}}
    <div class="flex items-start justify-between">
        <div class="flex flex-col items-start min-w-0">
            <div class="rounded-md px-2 text-xs font-bold tabular-nums min-h-[1.5rem] flex items-center {{ $iconBgClass }}">
                {{ $value }}
            </div>
        </div>
        <div class="{{ $kpiIconWrap }} {{ $iconBgClass }}">
            {{ $icon }}
        </div>
    </div>

    <div class="mt-4 flex min-h-0 flex-1 flex-col justify-end">
        <div class="flex w-full flex-col gap-1">
            <h3 class="font-bold text-slate-700 dark:text-navy-100">
                {{ $label }}
            </h3>

            @if (isset($description) && ! $description->isEmpty())
                <div class="mt-1 text-xs leading-relaxed text-slate-400 dark:text-navy-400">
                    {{ $description }}
                </div>
            @endif

            @isset($footer)
                <div class="mt-5 flex w-full shrink-0">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
