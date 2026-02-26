{{-- Delegates to lineone-button. Use for destructive actions (delete, reject). --}}
<x-lineone-button variant="danger" {{ $attributes->merge(['type' => 'submit']) }}>
    {{ $slot }}
</x-lineone-button>
