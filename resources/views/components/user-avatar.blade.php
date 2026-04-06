@props([
    'user',
    'sizeClass' => 'size-12',
])

@php
    $user->loadMissing(['providerProfile', 'recipientProfile']);
    $logoUrl = null;
    if ($user->hasRole('provider') && $user->providerProfile?->logo_url) {
        $logoUrl = $user->providerProfile->logo_url;
    } elseif ($user->hasRole('recipient') && $user->recipientProfile?->logo_url) {
        $logoUrl = $user->recipientProfile->logo_url;
    }
    $initial = strtoupper(mb_substr((string) ($user->name ?? 'U'), 0, 1));
@endphp

<div {{ $attributes->merge(['class' => 'avatar '.$sizeClass]) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="" class="{{ $sizeClass }} rounded-full object-cover ring-2 ring-slate-100 dark:ring-navy-600" />
    @else
        <div class="is-initial flex {{ $sizeClass }} items-center justify-center rounded-full bg-primary text-white dark:bg-accent">
            {{ $initial }}
        </div>
    @endif
</div>
