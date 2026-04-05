@props([
    'iconBgClass' => 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light',
    'valueWrapClass' => 'border-primary/25 bg-primary/5 text-slate-900 ring-primary/10 dark:border-accent/30 dark:bg-accent/10 dark:text-navy-50 dark:ring-accent/15',
])

@php
    $kpiIconWrap = 'flex size-10 shrink-0 items-center justify-center rounded-lg shadow-inner shadow-black/5';
@endphp

<div {{ $attributes->class(['card flex h-full min-h-0 flex-col p-5 border border-primary/15 dark:border-accent/25']) }}>
    {{-- Zone 1: icon (left) + value in cube (right) — dir=ltr; colors use --color-primary / --color-accent (nubl-gold) tokens --}}
    <div class="flex min-h-[2.75rem] shrink-0 flex-row items-center justify-between gap-2" dir="ltr">
        <div class="{{ $kpiIconWrap }} {{ $iconBgClass }}">
            {{ $icon }}
        </div>
        <div class="flex min-w-0 flex-1 items-center justify-end">
            <div @class([
                'inline-flex min-w-[2.75rem] max-w-full items-center justify-center rounded-lg border px-3 py-2 shadow-inner ring-1 ring-inset',
                $valueWrapClass,
            ])>
                {{ $value }}
            </div>
        </div>
    </div>

    <div class="mt-2.5 flex min-h-0 flex-1 flex-col justify-end">
        <div class="flex w-full flex-col gap-2">
            <div class="flex items-start">
                <div class="w-full text-start max-sm:text-center">
                    {{ $label }}
                </div>
            </div>

            @if (isset($description) && ! $description->isEmpty())
                <div class="min-h-0 overflow-hidden text-xs leading-snug text-slate-500 dark:text-navy-400">
                    {{ $description }}
                </div>
            @endif

            @isset($footer)
                <div class="flex min-h-[2.5rem] w-full shrink-0 items-center">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
