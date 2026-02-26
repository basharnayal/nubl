@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-slate-700 dark:text-navy-100']) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-red-400" aria-hidden="true">*</span>
    @endif
</label>
