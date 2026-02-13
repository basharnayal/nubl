{{-- Admin Navigation Menu (Desktop + Mobile in one file) --}}

{{-- Desktop Navigation Links --}}
<div class="hidden sm:flex sm:space-x-8">
    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->is('admin/users*') ? 'border-nubl-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ __('Users') }}
                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('admin.users.pending')">
                {{ __('Pending Users') }}
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>
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
    <x-responsive-nav-link :href="route('admin.users.pending')" :active="request()->routeIs('admin.users.*')">
        {{ __('Pending Users') }}
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
