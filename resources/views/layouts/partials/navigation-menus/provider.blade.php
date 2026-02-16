{{-- Provider Navigation Menu (Desktop + Mobile in one file) --}}

{{-- Desktop Navigation Links --}}
<div class="hidden sm:flex sm:space-x-8">
    <x-nav-link :href="route('provider.dashboard')" :active="request()->routeIs('provider.dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('provider/fulfillments*')">
        {{ __('Fulfillments') }}
    </x-nav-link>
    <x-nav-link :href="route('provider.menu-items.index')" :active="request()->routeIs('provider.menu-items.*')">
        {{ __('Inventory') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('provider/schedule*')">
        {{ __('Pickup Schedule') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('provider/analytics*')">
        {{ __('Analytics') }}
    </x-nav-link>
</div>

{{-- Mobile Navigation Links --}}
<div class="sm:hidden space-y-1">
    <x-responsive-nav-link :href="route('provider.dashboard')" :active="request()->routeIs('provider.dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('provider/fulfillments*')">
        {{ __('Fulfillments') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link :href="route('provider.menu-items.index')" :active="request()->routeIs('provider.menu-items.*')">
        {{ __('Inventory') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('provider/schedule*')">
        {{ __('Pickup Schedule') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('provider/analytics*')">
        {{ __('Analytics') }}
    </x-responsive-nav-link>
</div>