@props([
    'id' => null,
    'type' => 'info', // info, success, warning, danger
    'dismissible' => false,
    'icon' => true,
])

@php
$id = $id ?? 'alert-' . uniqid();
$typeClasses = [
    'info' => 'text-blue-800 bg-blue-50 border-blue-200',
    'success' => 'text-green-800 bg-green-50 border-green-200',
    'warning' => 'text-yellow-800 bg-yellow-50 border-yellow-200',
    'danger' => 'text-red-800 bg-red-50 border-red-200',
][$type] ?? 'text-blue-800 bg-blue-50 border-blue-200';

$iconSvg = [
    'info' => '<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>',
    'success' => '<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809-3.172-3.172a.5.5 0 0 0-.707 0L6.343 8.586a.5.5 0 1 0 .707.707l2.829-2.828 3.172 3.172a.5.5 0 0 0 .707-.707Z" clip-rule="evenodd"/>',
    'warning' => '<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>',
    'danger' => '<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd"/>',
][$type] ?? $iconSvg['info'];
@endphp

<div id="{{ $id }}" class="flex items-center p-4 mb-4 {{ $typeClasses }} rounded-lg border" role="alert">
    @if($icon)
    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        {!! $iconSvg !!}
    </svg>
    <span class="sr-only">{{ ucfirst($type) }}</span>
    @endif
    
    <div class="ms-3 text-sm font-medium">
        {{ $slot }}
    </div>
    
    @if($dismissible)
    <button type="button" class="ms-auto -mx-1.5 -my-1.5 {{ $typeClasses }} rounded-lg focus:ring-2 focus:ring-{{ $type === 'info' ? 'blue' : ($type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red')) }}-400 p-1.5 hover:bg-opacity-80 inline-flex items-center justify-center h-8 w-8" data-dismiss-target="#{{ $id }}" aria-label="Close">
        <span class="sr-only">Close</span>
        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
        </svg>
    </button>
    @endif
</div>
