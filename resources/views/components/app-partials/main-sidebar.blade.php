@php
    $sidebarMenu = $sidebarMenu ?? ['title' => '', 'items' => [[]]];
    $pageName = $pageName ?? '';
    $railBaseCore = 'flex items-center justify-center rounded-lg outline-hidden transition-colors duration-200';
    $railActive = 'text-primary bg-primary/10 hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:bg-navy-600 dark:text-accent-light dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90';
    $railInactive = 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25';
    $railPopperPlacement = app()->getLocale() === 'ar' ? 'left-start' : 'right-start';
@endphp
<div class="main-sidebar">
    <div class="flex h-full w-full flex-col items-center border-e border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
        <!-- Application Logo -->
        <div class="flex pt-4">
            <a href="{{ $dashboardUrl ?? route('dashboard') }}" class="block">
                <x-application-logo class="size-11 object-contain" />
            </a>
        </div>

        <!-- Rail: same entries as SidebarPanel (icons; submenus open a flyout) -->
        <div class="is-scrollbar-hidden flex grow flex-col space-y-2 overflow-y-auto overflow-x-visible pt-6">
            @foreach ($sidebarMenu['items'] as $menuGroup)
                @foreach ($menuGroup as $menuKey => $menu)
                    @php
                        $hasSubmenu = isset($menu['submenu']);
                        $topRoute = $menu['route_name'] ?? '';
                        $hasDirectRoute = $topRoute !== '' && Route::has($topRoute);
                        $validSubmenus = [];
                        if ($hasSubmenu) {
                            foreach ($menu['submenu'] as $subKey => $submenu) {
                                $subRoute = $submenu['route_name'] ?? '';
                                if ($subRoute !== '' && Route::has($subRoute)) {
                                    $validSubmenus[$subKey] = $submenu;
                                }
                            }
                        }
                        $isDisabledLeaf = !$hasSubmenu && !$hasDirectRoute;
                        $showRail = $hasDirectRoute || count($validSubmenus) > 0 || $isDisabledLeaf;
                    @endphp
                    @if (!$showRail)
                        @continue
                    @endif

                    @php
                        $icon = $menu['icon'] ?? 'layout';
                        if ($hasSubmenu) {
                            $parentActive = false;
                            foreach ($validSubmenus as $submenu) {
                                $subActive = isset($submenu['route_match'])
                                    ? request()->routeIs($submenu['route_match'])
                                    : (($submenu['route_name'] ?? '') === $pageName);
                                if ($subActive) {
                                    $parentActive = true;
                                    break;
                                }
                            }
                            $isActive = $parentActive;
                        } else {
                            $isActive = isset($menu['route_match'])
                                ? request()->routeIs($menu['route_match'])
                                : (($menu['route_name'] ?? '') === $pageName);
                        }
                        $isRequestsIcon = in_array($icon, ['clipboard-list', 'incoming-requests', 'inbox', 'truck'], true);
                        $railBox = $isRequestsIcon ? 'size-12' : 'size-11';
                        $iconClass = $isRequestsIcon ? 'size-8' : 'size-7';
                        $railClass = $railBaseCore . ' ' . $railBox . ' ' . ($isActive ? $railActive : $railInactive);
                    @endphp

                    @if ($hasSubmenu)
                        <div x-data="usePopper({ placement: '{{ $railPopperPlacement }}', offset: 12 })"
                            @click.outside="if(isShowPopper) isShowPopper = false"
                            class="relative flex justify-center">
                            <button type="button"
                                title="{{ $menu['title'] }}"
                                @click="isShowPopper = !isShowPopper"
                                x-ref="popperRef"
                                class="{{ $railClass }} cursor-pointer border-0 bg-transparent p-0">
                                <x-icons.sidebar-rail-icon :name="$icon" class="{{ $iconClass }}" />
                            </button>
                            <div :class="isShowPopper && 'show'" class="popper-root fixed z-[10050]" x-ref="popperRoot">
                                <div
                                    class="popper-box max-h-72 w-56 overflow-y-auto rounded-lg border border-slate-150 bg-white py-2 shadow-soft dark:border-navy-600 dark:bg-navy-700">
                                    <p class="border-b border-slate-150 px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-navy-600 dark:text-navy-300">
                                        {{ $menu['title'] }}
                                    </p>
                                    @foreach ($validSubmenus as $submenu)
                                        @php
                                            $submenuActive = isset($submenu['route_match'])
                                                ? request()->routeIs($submenu['route_match'])
                                                : (($submenu['route_name'] ?? '') === $pageName);
                                        @endphp
                                        <a href="{{ route($submenu['route_name']) }}"
                                            class="flex items-center px-4 py-2 text-sm tracking-wide transition-colors hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600 {{ $submenuActive ? 'font-medium text-primary dark:text-accent-light' : 'text-slate-700 dark:text-navy-100' }}">
                                            {{ $submenu['title'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-center">
                            @if ($hasDirectRoute)
                                <a href="{{ route($menu['route_name']) }}"
                                    title="{{ $menu['title'] }}"
                                    class="{{ $railClass }}">
                                    <x-icons.sidebar-rail-icon :name="$icon" class="{{ $iconClass }}" />
                                </a>
                            @else
                                <span title="{{ $menu['title'] }}"
                                    role="presentation"
                                    class="{{ $railClass }} cursor-not-allowed opacity-50">
                                    <x-icons.sidebar-rail-icon :name="$icon" class="{{ $iconClass }}" />
                                </span>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>

        <!-- Bottom: User Profile -->
        <div class="flex flex-col items-center space-y-3 py-3">
            <div x-data="usePopper({ placement: '{{ app()->getLocale() === 'ar' ? 'left-end' : 'right-end' }}', offset: 12 })" @click.outside="if(isShowPopper) isShowPopper = false" class="flex">
                <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="cursor-pointer">
                    <x-user-avatar :user="Auth::user()" size-class="size-12" />
                </button>
                <div :class="isShowPopper && 'show'" class="popper-root fixed" x-ref="popperRoot">
                    <div class="popper-box w-64 rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-700">
                        <div class="flex items-center space-x-4 rounded-t-lg bg-slate-100 py-5 px-4 dark:bg-navy-800">
                            <x-user-avatar :user="Auth::user()" size-class="size-14" />
                            <div>
                                <a href="{{ route('profile.edit') }}" class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
                                    {{ Auth::user()->name }}
                                </a>
                                <p class="text-xs text-slate-400 dark:text-navy-300">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col pt-2 pb-5">
                            <a href="{{ route('profile.edit') }}" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-hidden transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
                                <div class="flex size-8 items-center justify-center rounded-lg bg-warning text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span class="font-medium text-slate-700 dark:text-navy-100">{{ __('Profile') }}</span>
                            </a>
                            <div class="mt-3 px-4">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>{{ __('Log Out') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
