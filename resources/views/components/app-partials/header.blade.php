<nav class="header print:hidden">
    <div class="header-container relative flex w-full bg-white dark:bg-navy-750 print:hidden">
        <div class="flex w-full items-center justify-between">
            <!-- Left: Sidebar Toggle -->
            <div class="size-7">
                <button
                    class="menu-toggle cursor-pointer ml-0.5 flex size-7 flex-col justify-center space-y-1.5 text-primary outline-hidden focus:outline-hidden dark:text-accent-light/80"
                    :class="$store.global.isSidebarExpanded && 'active'"
                    @click="$store.global.isSidebarExpanded = !$store.global.isSidebarExpanded">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- Right: Mobile Search Toggle, Page Title, Dark Mode -->
            <div class="-mr-1.5 flex items-center space-x-2">
                @if(!empty($header))
                    <div class="text-sm font-medium text-slate-700 dark:text-navy-100">
                        {!! $header !!}
                    </div>
                @endif
                <button @click="$store.global.isDarkModeEnabled = !$store.global.isDarkModeEnabled"
                    class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg x-show="$store.global.isDarkModeEnabled"
                        x-transition:enter="transition-transform duration-200 ease-out absolute origin-top"
                        x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static"
                        class="size-6 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.75 3.412a.818.818 0 01-.07.917 6.332 6.332 0 00-1.4 3.971c0 3.564 2.98 6.494 6.706 6.494a6.86 6.86 0 002.856-.617.818.818 0 011.1 1.047C19.593 18.614 16.218 21 12.283 21 7.18 21 3 16.973 3 11.956c0-4.563 3.46-8.31 7.925-8.948a.818.818 0 01.826.404z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" x-show="!$store.global.isDarkModeEnabled"
                        x-transition:enter="transition-transform duration-200 ease-out absolute origin-top"
                        x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static"
                        class="size-6 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                {{-- Notifications (simplified, ready for NUBL) --}}
                <div x-data="usePopper({ placement: 'bottom-end', offset: 12 })" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
                    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef"
                        class="btn relative size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-500 dark:text-navy-100"
                            stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15.375 17.556h-6.75m6.75 0H21l-1.58-1.562a2.254 2.254 0 01-.67-1.596v-3.51a6.612 6.612 0 00-1.238-3.85 6.744 6.744 0 00-3.262-2.437v-.379c0-.59-.237-1.154-.659-1.571A2.265 2.265 0 0012 2c-.597 0-1.169.234-1.591.65a2.208 2.208 0 00-.659 1.572v.38c-2.621.915-4.5 3.385-4.5 6.287v3.51c0 .598-.24 1.172-.67 1.595L3 17.556h12.375zm0 0v1.11c0 .885-.356 1.733-.989 2.358A3.397 3.397 0 0112 22a3.397 3.397 0 01-2.386-.976 3.313 3.313 0 01-.989-2.357v-1.111h6.75z" />
                        </svg>
                        <span class="absolute -top-px -right-px flex size-3 items-center justify-center">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-80"></span>
                            <span class="inline-flex size-2 rounded-full bg-secondary"></span>
                        </span>
                    </button>
                    <div :class="isShowPopper && 'show'" class="popper-root" x-ref="popperRoot">
                        <div class="popper-box mx-4 mt-1 flex w-80 flex-col rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-800 dark:bg-navy-700 dark:shadow-soft-dark sm:m-0">
                            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-navy-600">
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-medium text-slate-700 dark:text-navy-100">{{ __('Notifications') }}</h3>
                                    <span class="badge h-5 rounded-full bg-primary/10 px-1.5 text-primary dark:bg-accent-light/15 dark:text-accent-light">4</span>
                                </div>
                            </div>
                            <div class="is-scrollbar-hidden max-h-64 space-y-4 overflow-y-auto px-4 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/10 dark:bg-warning/15">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-600 dark:text-navy-100">{{ __('Provider approval request') }}</p>
                                        <p class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('A new provider is waiting for account approval') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10 dark:bg-info/15">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-600 dark:text-navy-100">{{ __('New request received') }}</p>
                                        <p class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('A recipient has sent a new request') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success/10 dark:bg-success/15">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-600 dark:text-navy-100">{{ __('Account approved') }}</p>
                                        <p class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('Provider account has been approved successfully') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent-light/15">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-600 dark:text-navy-100">{{ __('New recipient registered') }}</p>
                                        <p class="mt-1 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">{{ __('A new recipient has joined the platform') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              
            </div>
        </div>
    </div>
</nav>
