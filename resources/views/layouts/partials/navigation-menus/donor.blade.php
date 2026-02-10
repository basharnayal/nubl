{{-- Donor Navigation Menu (Desktop + Mobile in one file) --}}

{{-- Desktop Navigation Links --}}
<div class="hidden sm:flex sm:space-x-8">
    <x-nav-link :href="route('donor.dashboard')" :active="request()->routeIs('donor.dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('donor/donations*')">
        {{ __('My Donations') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('donor/history*')">
        {{ __('Donation History') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('donor/statistics*')">
        {{ __('Statistics') }}
    </x-nav-link>
</div>

{{-- Mobile Navigation Links --}}
<div class="sm:hidden space-y-1">
    <x-responsive-nav-link :href="route('donor.dashboard')" :active="request()->routeIs('donor.dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('donor/donations*')">
        {{ __('My Donations') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('donor/history*')">
        {{ __('Donation History') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('donor/statistics*')">
        {{ __('Statistics') }}
    </x-responsive-nav-link>
</div>
