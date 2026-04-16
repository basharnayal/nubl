<x-app-layout title="{{ __('My Menu Items') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('My Menu Items') }}
                    </h2>
                    <a href="{{ route('provider.menu-items.create') }}"
                        class="btn bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                        {{ __('Add New Item') }}
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mt-3 p-6">
                    <form method="GET" action="{{ route('provider.menu-items.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-4">
                        <div class="flex-1 w-full">
                            <label for="search" class="sr-only">{{ __('Search') }}</label>
                            <div class="relative w-full">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="size-5 text-slate-500 dark:text-navy-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    class="form-input form-input-lineone pl-10"
                                    placeholder="{{ __('Search items...') }}">
                            </div>
                        </div>
                        <div class="w-full sm:w-48">
                            <select name="category_id"
                                class="form-select form-select-lineone ltr:pl-3 ltr:pr-9 rtl:pr-3 rtl:pl-10 rtl:bg-[position:left_0.5rem_center]"
                                onfocus="if (typeof TomSelect !== 'undefined' && !this.tomselect) new TomSelect(this, {create: false})">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ __($category->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="submit"
                                class="rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-focus focus:ring-4 focus:ring-primary/30 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/30">
                                {{ __('Filter') }}
                            </button>
                            @if(request()->anyFilled(['search', 'category_id', 'category']))
                                <a href="{{ route('provider.menu-items.index') }}"
                                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-900 hover:bg-slate-50 focus:ring-4 focus:ring-slate-200 dark:border-navy-450 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600 dark:focus:ring-navy-500">
                                    {{ __('Reset') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="card mt-3">
                    @if($menuItems->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                            {{ __('No menu items found. Start by adding one!') }}
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
                                        <th class="whitespace-nowrap ltr:rounded-tr-lg rtl:rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($menuItems as $item)
                                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="flex items-center space-x-4">
                                                    @if($item->image_url)
                                                        <img src="{{ $item->image_url }}" alt="" class="size-10 rounded-lg object-cover">
                                                    @else
                                                        <div class="flex size-10 items-center justify-center rounded-lg bg-slate-200 text-slate-400 dark:bg-navy-600 dark:text-navy-300 text-xs">
                                                            {{ __('No Img') }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="font-medium text-slate-700 dark:text-navy-100">{{ $item->name }}</span>
                                                        @if($item->sku)
                                                            <span class="block text-xs text-slate-500 dark:text-navy-300">SKU: {{ $item->sku }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ __($item->category) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="text-sm-plus font-medium text-slate-700 dark:text-navy-100">{{ number_format($item->price, 2) }} {{ __('SAR') }}</p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                @if($item->is_admin_blocked)
                                                    <span class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Blocked by Admin') }}</span>
                                                @elseif($item->is_active)
                                                    <span class="badge rounded-full bg-success/10 text-success dark:bg-success/15">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                @if($item->is_admin_blocked)
                                                    <span class="text-xs text-slate-400 dark:text-navy-400 italic">{{ __('Actions disabled by admin') }}</span>
                                                @else
                                                    <a href="{{ route('provider.menu-items.edit', $item) }}"
                                                        class="font-medium text-primary outline-hidden transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                                                        {{ __('Edit') }}
                                                    </a>
                                                    <span class="mx-2 text-slate-300 dark:text-navy-500">|</span>
                                                    <form action="{{ route('provider.menu-items.destroy', $item) }}" method="POST" class="inline-block"
                                                        onsubmit="return confirm('{{ __('Are you sure you want to deactivate this item?') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="font-medium text-error outline-hidden transition-colors hover:text-error-focus dark:hover:text-error/80">
                                                            {{ __('Deactivate') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                            {{ $menuItems->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
