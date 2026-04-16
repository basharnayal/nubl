<x-app-layout title="{{ __('Review Items') }} - {{ $provider->name }}" is-header-blur="true">
    <div x-data="{ 
        itemName: '', 
        itemActionUrl: '', 
        isBlocking: false,
        openModal(name, url, isBlocked) {
            this.itemName = name;
            this.itemActionUrl = url;
            this.isBlocking = !isBlocked;
            $dispatch('open-modal', 'confirm-toggle-block');
        }
    }" class="pt-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.menus.index') }}" class="btn size-9 rounded-full p-0 text-slate-500 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-navy-200 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Review Items') }}: {{ $provider->name }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-navy-300">{{ $provider->providerProfile?->business_name_ar ?? '' }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <form method="GET" action="{{ route('admin.menus.show', $provider) }}" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}"
                        class="form-input form-input-lineone min-w-32 sm:w-36">
                    
                    <select name="category_id" class="form-select form-select-lineone w-auto min-w-24">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ __($cat->name) }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select form-select-lineone w-auto min-w-24">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>{{ __('Blocked by Admin') }}</option>
                    </select>

                    <button type="submit" class="btn size-9 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    @if(request()->anyFilled(['search', 'category_id', 'status']))
                        <a href="{{ route('admin.menus.show', $provider) }}" class="btn size-9 rounded-full p-0 text-error hover:bg-error/10 focus:bg-error/10" title="{{ __('Reset') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card mt-4 shadow-soft">
            @if($menuItems->isEmpty())
                <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                    {{ __('No menu items found for this provider.') }}
                </div>
            @else
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <table class="is-hoverable w-full text-left rtl:text-right">
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap ltr:rounded-tl-lg rtl:rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Name') }}</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Category') }}</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Price') }}</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                <th class="whitespace-nowrap ltr:rounded-tr-lg rtl:rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($menuItems as $item)
                                <tr class="border-y border-transparent border-b-slate-200 transition-colors hover:bg-slate-50 dark:border-b-navy-500 dark:hover:bg-navy-600">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="" class="size-12 rounded-lg object-cover shadow-sm">
                                            @else
                                                <div class="flex size-12 items-center justify-center rounded-lg bg-slate-150 text-slate-400 dark:bg-navy-600 dark:text-navy-300 text-[10px] text-center px-1">
                                                    {{ __('No Photo') }}
                                                </div>
                                            @endif
                                            <div class="max-w-[200px] truncate">
                                                <span class="font-medium text-slate-700 dark:text-navy-100 block truncate" title="{{ $item->name }}">{{ $item->name }}</span>
                                                <span class="block text-xs text-slate-500 dark:text-navy-300 truncate" title="{{ $item->description }}">{{ $item->description }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                        <span class="badge rounded-full bg-slate-150 py-1 px-2 text-slate-800 dark:bg-navy-500 dark:text-navy-100 text-xs">{{ __($item->category) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                        <p class="text-sm-plus font-bold text-slate-700 dark:text-navy-100">{{ number_format($item->price, 2) }} <span class="text-[10px] font-normal opacity-70">{{ __('SAR') }}</span></p>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if($item->is_admin_blocked)
                                            <span class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Blocked') }}</span>
                                        @elseif($item->is_active)
                                            <span class="badge rounded-full bg-success/10 text-success dark:bg-success/15">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge rounded-full bg-slate-200 text-slate-600 dark:bg-navy-500 dark:text-navy-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-center">
                                        <button @click="openModal('{{ addslashes($item->name) }}', '{{ route('admin.menus.toggle-block', $item) }}', {{ $item->is_admin_blocked ? 'true' : 'false' }})" 
                                            class="btn size-9 rounded-full p-0 {{ $item->is_admin_blocked ? 'text-success hover:bg-success/10' : 'text-error hover:bg-error/10' }} transition-colors" 
                                            title="{{ $item->is_admin_blocked ? __('Unblock Item') : __('Block Item') }}">
                                            @if($item->is_admin_blocked)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-lineone-modal id="confirm-toggle-block" title="{{ __('Confirm Action') }}" size="md">
                    <div class="p-4 text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-error/10 text-error animate-pulse" x-show="isBlocking">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success/10 text-success" x-show="!isBlocking">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-navy-100" x-text="isBlocking ? '{{ __('Block Item?') }}' : '{{ __('Unblock Item?') }}'"></h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-navy-300">
                            <span x-text="isBlocking ? '{{ __('Are you sure you want to block') }}' : '{{ __('Are you sure you want to unblock') }}'"></span>
                            <span class="font-bold text-slate-700 dark:text-navy-100" x-text="itemName"></span>?
                        </p>
                        <div class="mt-6 flex justify-center space-x-3 rtl:space-x-reverse">
                            <button @click="$dispatch('close-modal', 'confirm-toggle-block')" class="btn bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 dark:bg-navy-500 dark:text-navy-100">{{ __('Cancel') }}</button>
                            <form :action="itemActionUrl" method="POST">
                                @csrf
                                <button type="submit" class="btn font-medium text-white transition-colors" :class="isBlocking ? 'bg-error hover:bg-error-focus' : 'bg-success hover:bg-success-focus'">
                                    <span x-text="isBlocking ? '{{ __('Block Now') }}' : '{{ __('Unblock Now') }}'"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </x-lineone-modal>

                @if($menuItems->hasPages())
                    <div class="flex flex-col justify-between space-y-4 border-t border-slate-200 px-4 py-4 dark:border-navy-500 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                        <div class="text-xs-plus text-slate-500 dark:text-navy-300">
                             {{ __('Showing') }} {{ $menuItems->firstItem() ?? 0 }} - {{ $menuItems->lastItem() ?? 0 }} {{ __('of') }} {{ $menuItems->total() }} {{ __('entries') }}
                        </div>
                        <div class="[&_.pagination]:flex [&_.pagination]:gap-1 [&_.pagination]:flex-wrap [&_.pagination_li]:rounded-lg [&_.pagination_li]:bg-slate-150 [&_.pagination_li]:dark:bg-navy-500 [&_.pagination_a]:flex [&_.pagination_a]:h-8 [&_.pagination_a]:min-w-[2rem] [&_.pagination_a]:items-center [&_.pagination_a]:justify-center [&_.pagination_a]:rounded-lg [&_.pagination_a]:px-3 [&_.pagination_a]:leading-tight [&_.pagination_a]:transition-colors [&_.pagination_a:hover]:bg-slate-300 [&_.pagination_a]:dark:hover:bg-navy-450 [&_.pagination_.active_a]:bg-primary [&_.pagination_.active_a]:text-white [&_.pagination_.active_a]:dark:bg-accent [&_.pagination_.disabled]:opacity-50">
                            {{ $menuItems->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
