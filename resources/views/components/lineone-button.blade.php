@props([
    'variant' => 'primary',
    'size' => 'md',
    'outline' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $variantClasses = [
        'primary' => 'bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-focus focus:bg-secondary-focus',
        'success' => 'bg-success text-white hover:bg-success-focus focus:bg-success-focus',
        'danger' => 'bg-error text-white hover:bg-error-focus focus:bg-error-focus',
        'warning' => 'bg-warning text-white hover:bg-warning-focus focus:bg-warning-focus',
        'info' => 'bg-info text-white hover:bg-info-focus focus:bg-info-focus',
        'slate' => 'bg-slate-150 text-slate-800 hover:bg-slate-200 focus:bg-slate-200 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450',
    ][$variant] ?? $variantClasses['primary'];
    if ($outline) {
        $variantClasses = match($variant) {
            'primary' => 'border-2 border-primary bg-transparent text-primary hover:bg-primary/10 dark:border-accent dark:text-accent dark:hover:bg-accent/10',
            'slate' => 'border-2 border-slate-300 bg-transparent text-slate-700 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600',
            default => 'border-2 border-current bg-transparent hover:bg-current/10 focus:bg-current/10 text-primary dark:text-accent',
        };
    }
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-5 py-2',
        'lg' => 'px-6 py-2.5 text-base',
    ][$size] ?? 'px-5 py-2';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "btn {$variantClasses} {$sizeClasses}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "btn {$variantClasses} {$sizeClasses}"]) }}>
        {{ $slot }}
    </button>
@endif
