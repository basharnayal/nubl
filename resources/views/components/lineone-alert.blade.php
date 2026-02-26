@props([
    'type' => 'info',
    'dismissible' => false,
    'id' => null,
])

@php
    $id = $id ?? 'alert-' . uniqid();
    $typeClasses = [
        'info' => 'bg-info/10 text-info dark:bg-info/15 dark:text-info',
        'success' => 'bg-success/10 text-success dark:bg-success/15 dark:text-success',
        'warning' => 'bg-warning/10 text-warning dark:bg-warning/15 dark:text-warning',
        'danger' => 'bg-error/10 text-error dark:bg-error/15 dark:text-error',
    ][$type] ?? $typeClasses['info'];
@endphp

<div id="{{ $id }}" x-data="{ show: true }" x-show="show" x-transition
    class="alert flex items-center rounded-lg px-4 py-4 {{ $typeClasses }} sm:px-5">
    <div class="flex-1">
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" @click="show = false" class="ms-2 shrink-0 rounded p-1 hover:opacity-80" aria-label="Close">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
