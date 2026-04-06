@props([
    'profile',
    'title',
    'variant' => 'card',
])

@php
    $logoUrl = $profile?->logo_url;
    $variants = [
        'card' => [
            'img' => 'size-12 shrink-0 rounded-2xl object-cover ring-1 ring-slate-200/80 dark:ring-navy-600 sm:size-14',
            'initials' => 'flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-sm font-bold text-primary dark:bg-accent/15 dark:text-accent-light sm:size-14',
        ],
        'compact-primary' => [
            'img' => 'size-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-navy-600',
            'initials' => 'is-initial flex size-10 items-center justify-center rounded-full bg-primary/10 text-sm-plus font-medium text-primary dark:bg-accent/10 dark:text-accent-light',
        ],
        'compact-secondary' => [
            'img' => 'size-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-navy-600',
            'initials' => 'is-initial flex size-10 items-center justify-center rounded-full bg-secondary/10 text-sm-plus font-medium text-secondary',
        ],
    ];
    $v = $variants[$variant] ?? $variants['card'];
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="" class="{{ $v['img'] }}" />
@else
    <div class="{{ $v['initials'] }}">{{ \App\Support\ProviderDisplay::initials($title) }}</div>
@endif
