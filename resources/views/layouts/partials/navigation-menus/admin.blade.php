{{-- Admin Navigation Menu (Desktop + Mobile in one file) --}}

{{-- Desktop Navigation Links --}}
<div class="hidden sm:flex sm:space-x-8">
    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('admin/users*')">
        {{ __('Users') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('admin/requests*')">
        {{ __('Requests') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('admin/reports*')">
        {{ __('Reports') }}
    </x-nav-link>
    <x-nav-link href="#" :active="request()->is('admin/settings*')">
        {{ __('Settings') }}
    </x-nav-link>
</div>

{{-- Mobile Navigation Links --}}
<div class="sm:hidden space-y-1">
    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('admin/users*')">
        {{ __('Users') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('admin/requests*')">
        {{ __('Requests') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('admin/reports*')">
        {{ __('Reports') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link href="#" :active="request()->is('admin/settings*')">
        {{ __('Settings') }}
    </x-responsive-nav-link>
</div>
