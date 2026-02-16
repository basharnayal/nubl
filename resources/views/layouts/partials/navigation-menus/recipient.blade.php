{{-- Recipient Navigation Menu (Desktop + Mobile in one file) --}}

{{-- Desktop Navigation Links --}}
<div class="hidden sm:flex sm:space-x-8">
    <x-nav-link :href="route('recipient.dashboard')" :active="request()->routeIs('recipient.dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link :href="route('recipient.providers.index')" :active="request()->routeIs('recipient.providers.*')">
        {{ __('Available providers') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('recipient/requests*')">
        {{ __('My Requests') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('recipient/qr*')">
        {{ __('QR Codes') }}
    </x-nav-link>
</div>

{{-- Mobile Navigation Links --}}
<div class="sm:hidden space-y-1">
    <x-responsive-nav-link :href="route('recipient.dashboard')" :active="request()->routeIs('recipient.dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link :href="route('recipient.providers.index')"
        :active="request()->routeIs('recipient.providers.*')">
        {{ __('Available providers') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('recipient/requests*')">
        {{ __('My Requests') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('recipient/qr*')">
        {{ __('QR Codes') }}
    </x-responsive-nav-link>
</div>
