@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:border-nubl-teal-500 focus:ring-nubl-teal-500']) }}>
