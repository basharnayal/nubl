{{-- Navigation Menu Router (Desktop) --}}
@role('admin')
    @include('layouts.partials.navigation-menus.admin')
@elserole('donor')
    @include('layouts.partials.navigation-menus.donor')
@elserole('recipient')
    @include('layouts.partials.navigation-menus.recipient')
@elserole('provider')
    @include('layouts.partials.navigation-menus.provider')
@else
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
@endrole
