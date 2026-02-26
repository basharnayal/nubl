{{-- Delegates to lineone-button. Use for primary submit actions (auth, profile). --}}
<x-lineone-button variant="primary" {{ $attributes->merge(['type' => 'submit']) }}>
    {{ $slot }}
</x-lineone-button>
