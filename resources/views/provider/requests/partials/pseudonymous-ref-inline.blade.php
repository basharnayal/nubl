@props([
    'ref',
    'variant' => 'compact',
])

@php
    $isAr = str_starts_with(app()->getLocale(), 'ar');
    $wrapperClasses = match ($variant) {
        'compact' => 'inline-flex min-w-0 max-w-full items-center gap-2 text-xs text-slate-500 dark:text-navy-400',
        'card', 'table' => 'inline-flex max-w-full items-center gap-3',
        default => 'inline-flex min-w-0 max-w-full items-center gap-2 text-xs text-slate-500 dark:text-navy-400',
    };
    $iconWrapClasses = match ($variant) {
        'compact' => 'flex size-6 shrink-0 items-center justify-center rounded-md bg-slate-200/90 dark:bg-navy-600',
        'card' => 'flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-200/90 dark:bg-navy-600',
        'table' => 'flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-200/90 dark:bg-navy-600',
        default => 'flex size-6 shrink-0 items-center justify-center rounded-md bg-slate-200/90 dark:bg-navy-600',
    };
    $iconInnerClasses = match ($variant) {
        'compact' => 'fa-solid fa-fingerprint text-[0.65rem] text-slate-600 dark:text-navy-200',
        'card', 'table' => 'fa-solid fa-fingerprint text-sm text-slate-600 dark:text-navy-200',
        default => 'fa-solid fa-fingerprint text-[0.65rem] text-slate-600 dark:text-navy-200',
    };
    $iconTitle = match ($variant) {
        'compact' => __('Reference'),
        'card', 'table' => __('Anonymous reference'),
        default => __('Reference'),
    };
    $refSpanClasses = match ($variant) {
        'compact' => 'min-w-0 truncate font-mono text-xs font-medium text-slate-700 dark:text-navy-100',
        'card' => 'min-w-0 truncate font-mono text-sm font-medium text-slate-700 dark:text-navy-100',
        'table' => 'min-w-0 truncate font-mono text-xs font-medium text-slate-700 dark:text-navy-100',
        default => 'min-w-0 truncate font-mono text-xs font-medium text-slate-700 dark:text-navy-100',
    };
@endphp
{{-- dir=ltr keeps R-… character order; AR puts ref before icon so icon sits to the right of the id. --}}
<div class="{{ $wrapperClasses }}" dir="ltr">
    @if ($isAr)
        <span class="{{ $refSpanClasses }}" title="{{ $ref }}">{{ $ref }}</span>
        <span class="{{ $iconWrapClasses }}" title="{{ $iconTitle }}" aria-hidden="true">
            <i class="{{ $iconInnerClasses }}"></i>
        </span>
    @else
        <span class="{{ $iconWrapClasses }}" title="{{ $iconTitle }}" aria-hidden="true">
            <i class="{{ $iconInnerClasses }}"></i>
        </span>
        <span class="{{ $refSpanClasses }}" title="{{ $ref }}">{{ $ref }}</span>
    @endif
</div>
