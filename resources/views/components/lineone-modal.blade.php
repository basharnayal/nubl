@props([
    'id',
    'title' => null,
    'size' => '2xl',
])

@php
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ][$size] ?? 'max-w-2xl';
@endphp

<div x-data="{ show: false }" x-on:open-modal.window="if ($event.detail === '{{ $id }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $id }}') show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-50 flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
    x-show="show" role="dialog" aria-modal="true" x-cloak
    style="display: none;">
    <div class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300" @click="show = false"
        x-show="show" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
    <div class="relative {{ $sizeClasses }} w-full max-h-[90vh] overflow-y-auto rounded-lg bg-white px-4 py-6 shadow-soft dark:bg-navy-700 sm:px-5"
        x-show="show" x-transition:enter="ease-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        @if($title)
            <div class="flex items-center justify-between border-b border-slate-150 pb-4 dark:border-navy-600">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-100">{{ $title }}</h3>
                <button @click="show = false" class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        <div class="pt-4">
            {{ $slot }}
        </div>
    </div>
</div>
