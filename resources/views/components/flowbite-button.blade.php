@props([
    'type' => 'button', // button, submit, reset
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, light, dark
    'size' => 'md', // xs, sm, md, lg, xl
    'pill' => false,
    'outline' => false,
    'disabled' => false,
])

@php
$baseClasses = 'font-medium rounded-lg focus:ring-4 focus:outline-none transition-colors';
$sizeClasses = [
    'xs' => 'text-xs px-2 py-1',
    'sm' => 'text-sm px-3 py-1.5',
    'md' => 'text-sm px-5 py-2.5',
    'lg' => 'text-base px-6 py-3',
    'xl' => 'text-lg px-8 py-4',
][$size] ?? 'text-sm px-5 py-2.5';

$variantClasses = [
    'primary' => $outline 
        ? 'text-blue-700 border border-blue-700 hover:bg-blue-700 hover:text-white focus:ring-blue-300' 
        : 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-blue-300',
    'secondary' => $outline
        ? 'text-gray-900 border border-gray-300 hover:bg-gray-100 focus:ring-gray-100'
        : 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-100',
    'success' => $outline
        ? 'text-green-700 border border-green-700 hover:bg-green-700 hover:text-white focus:ring-green-300'
        : 'text-white bg-green-700 hover:bg-green-800 focus:ring-green-300',
    'danger' => $outline
        ? 'text-red-700 border border-red-700 hover:bg-red-700 hover:text-white focus:ring-red-300'
        : 'text-white bg-red-700 hover:bg-red-800 focus:ring-red-300',
    'warning' => $outline
        ? 'text-yellow-700 border border-yellow-700 hover:bg-yellow-700 hover:text-white focus:ring-yellow-300'
        : 'text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-yellow-300',
    'info' => $outline
        ? 'text-cyan-700 border border-cyan-700 hover:bg-cyan-700 hover:text-white focus:ring-cyan-300'
        : 'text-white bg-cyan-700 hover:bg-cyan-800 focus:ring-cyan-300',
    'light' => $outline
        ? 'text-gray-900 border border-gray-300 hover:bg-gray-100 focus:ring-gray-100'
        : 'text-gray-900 bg-gray-200 hover:bg-gray-300 focus:ring-gray-100',
    'dark' => $outline
        ? 'text-white border border-gray-800 hover:bg-gray-800 focus:ring-gray-800'
        : 'text-white bg-gray-800 hover:bg-gray-900 focus:ring-gray-800',
][$variant] ?? 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-blue-300';

$pillClasses = $pill ? 'rounded-full' : '';
$disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
@endphp

<button 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses $pillClasses $disabledClasses"]) }}
    @if($disabled) disabled @endif
>
    {{ $slot }}
</button>
