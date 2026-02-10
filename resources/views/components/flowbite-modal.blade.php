@props([
    'id',
    'title' => null,
    'size' => '2xl', // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
    'showCloseButton' => true,
    'footer' => null,
])

@php
$sizeClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
][$size] ?? 'sm:max-w-2xl';
@endphp

<!-- Modal -->
<div id="{{ $id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full {{ $sizeClasses }} max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            @if($title || $showCloseButton)
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                @if($title)
                <h3 class="text-xl font-semibold text-gray-900">
                    {{ $title }}
                </h3>
                @else
                <div></div>
                @endif
                @if($showCloseButton)
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="{{ $id }}">
                    <span class="sr-only">Close modal</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
                @endif
            </div>
            @endif
            
            <!-- Modal body -->
            <div class="p-4 md:p-5 space-y-4">
                {{ $slot }}
            </div>
            
            <!-- Modal footer -->
            @if($footer)
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>
