{{-- Navigation Menu Router (Mobile) - Uses same files as desktop --}}
@role('admin')
    @include('layouts.partials.navigation-menus.admin')
@elserole('donor')
    @include('layouts.partials.navigation-menus.donor')
@elserole('recipient')
    @include('layouts.partials.navigation-menus.recipient')
@elserole('provider')
    @include('layouts.partials.navigation-menus.provider')
@else
    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>
@endrole
