@props([
    'title' => null,
    'subtitle' => null,
    'image' => null,
    'imageAlt' => null,
    'footer' => null,
    'href' => null,
    'class' => '',
])

@php
$cardClasses = 'max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow ' . $class;
if ($href) {
    $cardClasses .= ' hover:bg-gray-50 cursor-pointer';
}
@endphp

@if($href)
<a href="{{ $href }}" class="{{ $cardClasses }}">
@else
<div class="{{ $cardClasses }}">
@endif

    @if($image)
    <img class="rounded-t-lg w-full" src="{{ $image }}" alt="{{ $imageAlt ?? $title }}" />
    @endif

    @if($title || $subtitle)
    <div class="{{ $image ? 'pt-4' : '' }}">
        @if($title)
        <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900">
            {{ $title }}
        </h5>
        @endif
        
        @if($subtitle)
        <p class="mb-3 font-normal text-gray-700">
            {{ $subtitle }}
        </p>
        @endif
    </div>
    @endif

    <div class="{{ ($title || $subtitle) && !$footer ? 'mt-4' : '' }}">
        {{ $slot }}
    </div>

    @if($footer)
    <div class="mt-4 pt-4 border-t border-gray-200">
        {{ $footer }}
    </div>
    @endif

@if($href)
</a>
@else
</div>
@endif
