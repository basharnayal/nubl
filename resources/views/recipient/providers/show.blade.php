<x-app-layout title="{{ \App\Support\ProviderDisplay::businessTitle($provider->providerProfile, $provider->name) }} - {{ __('Menu') }}"
    is-header-blur="true">
    <div class="pb-24 pt-4 lg:pb-8">
        <div class="relative">
            <div class="lg:flex lg:gap-8">
                {{-- Left Column: Provider Info & Menu --}}
                <div class="flex-1">
                    @php
                        $capacityOn = $provider->accepting_orders
                            && $provider->providerOperatingInfo
                            && $provider->providerOperatingInfo->daily_capacity > 0;
                        $businessTitle = \App\Support\ProviderDisplay::businessTitle($provider->providerProfile, $provider->name);
                        $headerLogoUrl = $provider->providerProfile?->logo_url;
                        $dayKey = strtolower(now()->format('l'));
                        $today = $provider->providerOperatingInfo?->operating_hours[$dayKey] ?? null;
                        $openNow = false;
                        if ($today && empty($today['closed']) && ! empty($today['open'] ?? null) && ! empty($today['close'] ?? null)) {
                            try {
                                $openT = today()->copy()->setTimeFromTimeString($today['open']);
                                $closeT = today()->copy()->setTimeFromTimeString($today['close']);
                                $openNow = now()->between($openT, $closeT);
                            } catch (\Throwable) {
                                $openNow = false;
                            }
                        }
                    @endphp
                    {{-- Provider bar (horizontal) + meta --}}
                    <div class="card mb-6 overflow-hidden p-4 sm:p-5">
                        <div
                            class="flex w-full items-center gap-3 rounded-2xl border border-primary/25 bg-primary/[0.04] px-4 py-3 dark:border-accent/35 dark:bg-accent/[0.06] sm:gap-4 sm:px-5 sm:py-3.5">
                            @if ($headerLogoUrl)
                                <div
                                    class="size-11 shrink-0 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/90 dark:bg-navy-800 dark:ring-navy-600 sm:size-12">
                                    <img src="{{ $headerLogoUrl }}" alt=""
                                        class="size-full object-cover" />
                                </div>
                            @else
                                <div
                                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light sm:size-12"
                                    aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 sm:size-7" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                            d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2M3.75 3.47v-.84A.75.75 0 014.5 2h15a.75.75 0 01.75.75v.84m-16.5 0V9a.75.75 0 00.75.75h14.25a.75.75 0 00.75-.75V3.47m-16.5 0A48.11 48.11 0 0112 2.25c2.414 0 4.722.284 6.878.849M3.75 3.47V9m0-5.53v5.53" />
                                    </svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1 text-start">
                                <h1 class="truncate text-lg font-bold tracking-tight text-slate-800 dark:text-navy-100 sm:text-xl">
                                    {{ $businessTitle }}
                                </h1>
                                <p class="truncate text-sm text-slate-600 dark:text-navy-300">
                                    {{ \App\Support\ProviderDisplay::businessCategoryLine($provider->providerProfile->business_category) ?? __('General Provider') }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if($capacityOn)
                                    <span
                                        class="badge whitespace-nowrap rounded-full bg-success/10 text-success dark:bg-success/15">{{ __('Capacity') }}:
                                        ON</span>
                                @else
                                    <span
                                        class="badge whitespace-nowrap rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Capacity') }}:
                                        OFF</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if($today && empty($today['closed']) && $openNow)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-3 py-1 text-xs font-medium text-success dark:border-success/40 dark:bg-success/15">
                                    <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Open now') }}
                                </span>
                            @elseif($today && empty($today['closed']))
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:border-navy-500 dark:bg-navy-600/50 dark:text-navy-200">
                                    <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Closed now') }}
                                </span>
                            @endif
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-white/80 px-3 py-1 text-xs text-slate-600 dark:border-accent/25 dark:bg-navy-800/80 dark:text-navy-300">
                                <svg class="size-3.5 shrink-0 text-primary dark:text-accent-light" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="max-w-[14rem] truncate sm:max-w-xs">{{ \App\Support\ProviderDisplay::locationLine($provider->providerProfile) }}</span>
                            </span>
                            @if($today)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 dark:border-navy-500 dark:text-navy-300">
                                    <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ isset($today['closed']) && $today['closed'] ? __('Closed Today') : ($today['open'].' - '.$today['close']) }}
                                </span>
                            @endif
                        </div>

                        @if($provider->providerOperatingInfo && !empty($provider->providerOperatingInfo->service_type))
                            <p class="mt-3 flex items-center gap-2 text-sm text-slate-500 dark:text-navy-400">
                                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ __('Service Type') }}:
                                {{ \App\Support\ProviderDisplay::serviceTypeLine($provider->providerOperatingInfo->service_type) }}
                            </p>
                        @endif
                        @if($provider->providerOperatingInfo && filled($provider->providerOperatingInfo->pickup_notes))
                            <p
                                class="mt-3 rounded-xl border border-primary/20 bg-primary/[0.06] p-3 text-sm text-slate-700 dark:border-accent/30 dark:bg-accent/10 dark:text-navy-100">
                                <span class="font-semibold">{{ __('Pickup / delivery notes') }}:</span>
                                {{ $provider->providerOperatingInfo->pickup_notes }}
                            </p>
                        @endif
                    </div>

                    @if($errors->any())
                        <div class="mb-4">
                            <x-lineone-alert type="danger" dismissible>
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </x-lineone-alert>
                        </div>
                    @endif

                    @if(session('exceeds_allowance'))
                        <div x-data="{ popupOpen: true }" x-show="popupOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm px-4">
                            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-soft dark:bg-navy-700">
                                <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-100 mb-2">
                                    {{ __('Weekly Limit Exceeded') }}
                                </h3>
                                <p class="text-slate-600 dark:text-navy-300 text-sm mb-6">
                                    {{ __('Your request amount exceeds the available weekly allowance. You can cancel to reconsider, or send the request for manual admin review.') }}
                                </p>
                                <div class="flex justify-end gap-3">
                                    <form action="{{ route('recipient.requests.cancel-throttle') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn border border-slate-300 bg-white font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                                            {{ __('Cancel') }}
                                        </button>
                                    </form>
                                    <button type="button" class="btn bg-primary font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus"
                                        onclick="document.getElementById('force-admin-input').value = '1'; document.getElementById('submit-request-form').submit();">
                                        {{ __('Send Request Review') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Filters --}}
                    <div class="card mb-4 p-4">
                        <form method="GET" action="{{ route('recipient.providers.show', $provider) }}"
                            class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="relative w-full sm:w-64">
                                <label for="search" class="sr-only">{{ __('Search') }}</label>
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="size-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    class="form-input form-input-lineone pl-9"
                                    placeholder="{{ __('Search items...') }}">
                            </div>
                            <div class="w-full sm:w-48">
                                <label for="category_id" class="sr-only">{{ __('Category') }}</label>
                                <select name="category_id" id="category_id"
                                    class="form-select form-select-lineone ltr:pl-3 ltr:pr-9 rtl:pr-3 rtl:pl-10 rtl:bg-[position:left_0.5rem_center]">
                                    <option value="">{{ __('All Categories') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">{{ __('Filter') }}</button>
                                @if(request()->anyFilled(['search', 'category_id']))
                                    <a href="{{ route('recipient.providers.show', $provider) }}"
                                        class="btn border border-slate-300 text-slate-800 hover:bg-slate-150 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500">{{ __('Reset') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Menu list (horizontal rows) --}}
                    <div class="flex flex-col gap-3 sm:gap-4">
                        @forelse($menuItems as $item)
                            <div
                                class="card group relative flex flex-row overflow-hidden transition-shadow hover:shadow-soft dark:hover:shadow-soft-dark">
                                @if(!$item->is_active)
                                    <div
                                        class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 dark:bg-navy-900/60">
                                        <span
                                            class="badge rounded-full bg-slate-600 px-3 py-1 text-sm font-bold text-white dark:bg-navy-500">{{ __('Unavailable') }}</span>
                                    </div>
                                @endif

                                <div
                                    class="relative h-28 w-28 shrink-0 overflow-hidden bg-slate-100 sm:h-32 sm:w-32 dark:bg-navy-700">
                                    @if($item->image_url)
                                        <img class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            src="{{ $item->image_url }}" alt="{{ $item->name }}">
                                    @else
                                        <div class="flex size-full items-center justify-center text-slate-400 dark:text-navy-400">
                                            <svg class="size-10 sm:size-12" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col justify-center p-4 sm:p-5">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                        <div class="min-w-0 flex-1">
                                            <h5
                                                class="line-clamp-2 text-base font-bold tracking-tight text-slate-800 dark:text-navy-100 sm:text-lg">
                                                {{ $item->name }}
                                            </h5>
                                            <p class="mt-1 line-clamp-2 text-sm text-slate-600 dark:text-navy-300">
                                                {{ $item->description }}
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end">
                                            <span
                                                class="badge w-fit rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary dark:bg-accent-light/15 dark:text-accent-light">{{ number_format($item->price, 2) }}
                                                <x-sar-symbol /></span>
                                            @if($item->max_per_request)
                                                <span class="text-xs font-medium text-warning sm:text-end">{{ __('Max') }}
                                                    {{ $item->max_per_request }} /{{ __('req') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @php $itemMax = $item->max_per_request ?? 99; @endphp
                                    <div class="mt-3 flex justify-end">
                                        <div id="menu-controls-{{ $item->id }}"
                                            class="menu-item-controls inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200/90 bg-slate-100/95 py-1.5 ps-1.5 pe-2 shadow-sm dark:border-navy-600 dark:bg-navy-700/80 sm:gap-2.5 sm:ps-2 sm:pe-2.5"
                                            data-item-id="{{ $item->id }}"
                                            data-price="{{ $item->price }}"
                                            data-max="{{ $itemMax }}"
                                            data-item-name="{{ e($item->name) }}"
                                            data-capacity-on="{{ $capacityOn ? '1' : '0' }}"
                                            data-item-active="{{ $item->is_active ? '1' : '0' }}"
                                            role="group"
                                            aria-label="{{ __('Quantity') }} — {{ $item->name }}">
                                            <div class="flex shrink-0 justify-center">
                                                <button type="button"
                                                    class="menu-trash flex size-8 items-center justify-center rounded-full text-slate-300 disabled:pointer-events-none disabled:cursor-not-allowed dark:text-navy-500"
                                                    onclick="menuRemove({{ $item->id }})"
                                                    aria-label="{{ __('Remove') }}"
                                                    disabled>
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <span
                                                class="menu-line-total shrink-0 whitespace-nowrap text-sm font-semibold tabular-nums text-slate-800 dark:text-navy-100">0.00
                                                <x-sar-symbol /></span>
                                            <div class="flex items-center gap-1">
                                                <button type="button"
                                                    class="menu-minus flex size-7 shrink-0 items-center justify-center rounded-full border border-slate-300/90 bg-white text-sm font-bold leading-none text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-navy-500 dark:bg-navy-600 dark:text-navy-100 dark:hover:bg-navy-500"
                                                    onclick="menuAdjustQty({{ $item->id }}, -1)"
                                                    aria-label="{{ __('Decrease quantity') }}"
                                                    {{ (!$item->is_active || !$capacityOn) ? 'disabled' : '' }}>−</button>
                                                <span
                                                    class="menu-qty-display min-w-[1.25rem] text-center text-sm font-bold text-slate-800 dark:text-navy-100">0</span>
                                                <button type="button"
                                                    class="menu-plus flex size-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-bold leading-none text-slate-800 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-navy-500 dark:bg-navy-600 dark:text-navy-100 dark:hover:bg-navy-500"
                                                    onclick="menuAdjustQty({{ $item->id }}, 1)"
                                                    aria-label="{{ __('Increase quantity') }}"
                                                    {{ (!$item->is_active || !$capacityOn) ? 'disabled' : '' }}>+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full rounded-lg py-12 text-center">
                                <p class="text-slate-500 dark:text-navy-300">
                                    {{ __('No menu items found for this provider.') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Right Column: Cart & Summary --}}
                <div class="mt-8 lg:mt-0 lg:w-96">
                    <div class="sticky top-6">
                        {{-- Weekly Allowance Card --}}
                        <div class="card mb-6 p-6">
                            <h3 class="mb-4 text-base font-bold text-slate-800 dark:text-navy-100">
                                {{ __('Weekly Allowance') }}
                            </h3>

                            @php
                                $allowance = $weeklyLimit ?? 400;
                                $remaining = max(0, $allowance - $weeklyUsed);
                                $percent = min(100, ($allowance > 0 ? ($weeklyUsed / $allowance) * 100 : 0));
                                $color = $percent > 90 ? 'bg-error' : ($percent > 75 ? 'bg-warning' : 'bg-success');
                            @endphp

                            <div class="mb-2 h-2.5 w-full rounded-full bg-slate-200 dark:bg-navy-600">
                                <div class="{{ $color }} h-2.5 rounded-full transition-all duration-500"
                                    style="width: {{ $percent }}%"></div>
                            </div>

                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-navy-300">{{ __('Used') }}:</span>
                                <span
                                    class="font-bold text-slate-800 dark:text-navy-100">{{ number_format($weeklyUsed, 2) }}
                                    <x-sar-symbol /></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-navy-300">{{ __('Remaining') }}:</span>
                                <span
                                    class="font-bold {{ $remaining < 50 ? 'text-error' : 'text-success' }}">{{ number_format($remaining, 2) }}
                                    <x-sar-symbol /></span>
                            </div>
                        </div>

                        {{-- Cart Summary (Desktop) --}}
                        <div class="hidden lg:block">
                            <div class="card border-primary/20 p-6 dark:border-accent/20">
                                <h3 class="mb-4 text-base font-bold text-slate-800 dark:text-navy-100">
                                    {{ __('Your Request') }}
                                </h3>

                                <div id="cart-items"
                                    class="is-scrollbar-hidden mb-4 max-h-60 space-y-3 overflow-y-auto">
                                    <div class="py-4 text-center text-sm text-slate-500 dark:text-navy-400">
                                        {{ __('No items selected.') }}
                                    </div>
                                </div>

                                <div class="mb-4 border-t border-slate-200 pt-4 dark:border-navy-600">
                                    <div class="flex justify-between font-bold text-slate-800 dark:text-navy-100">
                                        <span>{{ __('Total') }}:</span>
                                        <span id="cart-total">0.00 <x-sar-symbol /></span>
                                    </div>
                                    <div class="mt-1 flex justify-between text-xs text-slate-500 dark:text-navy-400">
                                        <span>{{ __('After Request') }}:</span>
                                        <span id="cart-projected-week" class="font-medium"></span>
                                    </div>
                                    <p id="allowance-warning" class="mt-2 hidden text-xs font-bold text-error">
                                        {{ __('Your request exceeds your available allowance for this period.') }}
                                    </p>
                                </div>

                                @error('allowance')
                                    <p class="mb-4 text-sm font-bold text-error">{{ $message }}</p>
                                @enderror
                                <form id="submit-request-form" action="{{ route('recipient.requests.store') }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                    <input type="hidden" id="force-admin-input" name="force_admin_review" value="0">
                                    <div id="form-items-container"></div>

                                    <button type="submit" id="submit-btn" disabled
                                        class="btn w-full bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus">
                                        {{ __('Submit Request') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Sticky Cart Summary --}}
    <div
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] dark:border-navy-600 dark:bg-navy-800 lg:hidden">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Total Items') }}: <span id="mobile-count"
                        class="font-bold text-slate-800 dark:text-navy-100">0</span></p>
                <p class="text-lg font-bold text-primary dark:text-accent-light" id="mobile-total">0.00 <x-sar-symbol />
                </p>
            </div>
            <button type="button" onclick="document.getElementById('submit-btn').click()" id="mobile-submit-btn"
                disabled
                class="btn bg-primary px-6 py-2 text-white hover:bg-primary-focus disabled:cursor-not-allowed disabled:opacity-50 dark:bg-accent dark:hover:bg-accent-focus">
                {{ __('Submit Request') }}
            </button>
        </div>
    </div>

    <script>
        let cart = [];
        const weeklyUsed = {{ $weeklyUsed }};
        const allowance = {{ $weeklyLimit ?? 400 }};
        const sarLabel = '';

        function getMenuMeta(id) {
            const el = document.getElementById('menu-controls-' + id);
            if (!el) return null;
            return {
                id: Number(el.dataset.itemId),
                name: el.dataset.itemName,
                price: parseFloat(el.dataset.price),
                max: parseInt(el.dataset.max, 10) || 99,
            };
        }

        function menuRemove(id) {
            cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function menuAdjustQty(id, delta) {
            const meta = getMenuMeta(id);
            if (!meta) return;
            const existing = cart.find(i => i.id === id);
            let qty = existing ? existing.qty : 0;
            qty += delta;
            if (qty < 0) qty = 0;
            if (meta.max && qty > meta.max) qty = meta.max;
            if (qty === 0) {
                cart = cart.filter(i => i.id !== id);
            } else if (existing) {
                existing.qty = qty;
            } else {
                cart.push({ id: meta.id, name: meta.name, price: meta.price, max: meta.max, qty });
            }
            renderCart();
        }

        function removeFromCart(id) {
            menuRemove(id);
        }

        function updateMenuControlRows() {
            document.querySelectorAll('.menu-item-controls').forEach(el => {
                const id = Number(el.dataset.itemId);
                const row = cart.find(i => i.id === id);
                const qty = row ? row.qty : 0;
                const price = parseFloat(el.dataset.price);
                const max = parseInt(el.dataset.max, 10) || 99;
                const lineTotal = qty * price;
                const locked = el.dataset.capacityOn !== '1' || el.dataset.itemActive !== '1';
                const totalEl = el.querySelector('.menu-line-total');
                const qtyEl = el.querySelector('.menu-qty-display');
                const trash = el.querySelector('.menu-trash');
                const minus = el.querySelector('.menu-minus');
                const plus = el.querySelector('.menu-plus');
                if (totalEl) totalEl.textContent = lineTotal.toFixed(2) + ' ' + sarLabel;
                if (qtyEl) qtyEl.textContent = String(qty);
                if (trash) {
                    const canDelete = !locked && qty > 0;
                    trash.disabled = !canDelete;
                    if (canDelete) {
                        trash.classList.remove('text-slate-300', 'dark:text-navy-500');
                        trash.classList.add('text-error', 'hover:bg-error/10', 'hover:text-error-focus');
                    } else {
                        trash.classList.remove('text-error', 'hover:bg-error/10', 'hover:text-error-focus');
                        trash.classList.add('text-slate-300', 'dark:text-navy-500');
                    }
                }
                if (minus) minus.disabled = locked || qty <= 0;
                if (plus) plus.disabled = locked || qty >= max;
            });
        }

        function renderCart() {
            const container = document.getElementById('cart-items');
            container.innerHTML = '';
            let total = 0, count = 0;

            if (cart.length === 0) {
                container.innerHTML = '<div class="py-4 text-center text-sm text-slate-500 dark:text-navy-400">{{ __("No items selected.") }}</div>';
            } else {
                cart.forEach(item => {
                    const lineTotal = item.price * item.qty;
                    total += lineTotal;
                    count += item.qty;
                    const el = document.createElement('div');
                    el.className = 'flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm dark:border-navy-600 dark:bg-navy-750/90';
                    el.innerHTML = `
                        <div class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-slate-700 dark:text-navy-100">${escapeHtml(item.name)}</span>
                            <span class="text-xs text-slate-500 dark:text-navy-400">${item.qty} × ${item.price.toFixed(2)}</span>
                        </div>
                        <span class="shrink-0 font-bold tabular-nums text-slate-800 dark:text-navy-100">${lineTotal.toFixed(2)} ${sarLabel}</span>
                        <button type="button" onclick="menuRemove(${item.id})" class="shrink-0 p-1 text-error hover:text-error-focus" aria-label="{{ __('Remove') }}">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        </button>
                    `;
                    container.appendChild(el);
                });
            }

            const projected = weeklyUsed + total;
            const exceeds = projected > allowance;

            document.getElementById('cart-total').textContent = total.toFixed(2) + ' ' + sarLabel;
            document.getElementById('cart-projected-week').textContent = projected.toFixed(2) + ' / ' + allowance + ' ' + sarLabel;
            document.getElementById('cart-projected-week').className = exceeds ? 'font-bold text-error' : 'font-medium text-success';

            const warningEl = document.getElementById('allowance-warning');
            const submitBtn = document.getElementById('submit-btn');
            const mobileSubmitBtn = document.getElementById('mobile-submit-btn');

            if (cart.length === 0) {
                warningEl.classList.add('hidden');
                submitBtn.disabled = true;
                mobileSubmitBtn.disabled = true;
            } else if (exceeds) {
                warningEl.classList.remove('hidden');
                submitBtn.disabled = false;
                mobileSubmitBtn.disabled = false;
            } else {
                warningEl.classList.add('hidden');
                submitBtn.disabled = false;
                mobileSubmitBtn.disabled = false;
            }

            document.getElementById('mobile-total').textContent = total.toFixed(2) + ' ' + sarLabel;
            document.getElementById('mobile-count').textContent = count;

            const formContainer = document.getElementById('form-items-container');
            formContainer.innerHTML = '';
            cart.forEach((item, index) => {
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = `items[${index}][id]`;
                inputId.value = item.id;
                const inputQty = document.createElement('input');
                inputQty.type = 'hidden';
                inputQty.name = `items[${index}][quantity]`;
                inputQty.value = item.qty;
                formContainer.appendChild(inputId);
                formContainer.appendChild(inputQty);
            });

            updateMenuControlRows();
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        renderCart();

        document.addEventListener('DOMContentLoaded', function() {
            @if(old('items'))
                @foreach(old('items') as $idx => $item)
                    (function() {
                        const meta = getMenuMeta({{ $item['id'] }});
                        if (meta) {
                            cart.push({ id: meta.id, name: meta.name, price: meta.price, max: meta.max, qty: {{ $item['quantity'] }} });
                        }
                    })();
                @endforeach
                renderCart();
            @endif
        });
    </script>
</x-app-layout>