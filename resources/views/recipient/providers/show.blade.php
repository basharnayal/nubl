<x-app-layout title="{{ $provider->providerProfile->business_name_en ?? $provider->name }} - {{ __('Menu') }}"
    is-header-blur="true">
    <div class="pb-24 pt-4 lg:pb-8">
        <div class="relative">
            <div class="lg:flex lg:gap-8">
                {{-- Left Column: Provider Info & Menu --}}
                <div class="flex-1">
                    {{-- Provider Info Card --}}
                    <div class="card mb-6 border-l-4 border-l-primary p-6 dark:border-l-accent">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="mb-2 text-2xl font-bold text-slate-800 dark:text-navy-100">
                                    {{ $provider->providerProfile->business_name_en ?? $provider->name }}
                                </h1>
                                <p class="mb-4 text-slate-600 dark:text-navy-300">
                                    {{ $provider->providerProfile->business_category ? implode(', ', $provider->providerProfile->business_category) : __('General Provider') }}
                                </p>
                            </div>
                            @php
                                $capacityOn = $provider->providerOperatingInfo && $provider->providerOperatingInfo->daily_capacity > 0;
                            @endphp
                            <div>
                                @if($capacityOn)
                                    <span
                                        class="badge rounded-full bg-success/10 text-success dark:bg-success/15">{{ __('Capacity') }}:
                                        ON</span>
                                @else
                                    <span
                                        class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Capacity') }}:
                                        OFF</span>
                                @endif
                            </div>
                        </div>

                        <div
                            class="mt-2 grid grid-cols-1 gap-4 text-sm text-slate-500 md:grid-cols-2 dark:text-navy-400">
                            <p class="flex items-center">
                                <svg class="mr-2 size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $provider->providerProfile->location ?? ($provider->providerProfile->city . ', ' . $provider->providerProfile->region) }}
                            </p>
                            @if($provider->providerOperatingInfo && !empty($provider->providerOperatingInfo->service_type))
                                <p class="flex items-center">
                                    <svg class="mr-2 size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                    {{ __('Service Type') }}:
                                    {{ implode(', ', array_map(fn($s) => ucfirst(str_replace('_', ' ', $s)), $provider->providerOperatingInfo->service_type)) }}
                                </p>
                            @endif
                            <p class="flex items-center">
                                <svg class="mr-2 size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ __('Hours') }}:
                                @if($provider->providerOperatingInfo && isset($provider->providerOperatingInfo->operating_hours[strtolower(now()->format('l'))]))
                                    @php $today = $provider->providerOperatingInfo->operating_hours[strtolower(now()->format('l'))]; @endphp
                                    {{ isset($today['closed']) && $today['closed'] ? __('Closed Today') : ($today['open'] . ' - ' . $today['close']) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>

                    @if(Session::has('success'))
                        <div class="mb-4">
                            <x-lineone-alert type="success" dismissible>{{ Session::get('success') }}</x-lineone-alert>
                        </div>
                    @endif
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

                    {{-- Menu Grid --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @forelse($menuItems as $item)
                            <div
                                class="card group relative flex h-full flex-col overflow-hidden transition-shadow hover:shadow-soft dark:hover:shadow-soft-dark">
                                @if(!$item->is_active)
                                    <div
                                        class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 dark:bg-navy-900/60">
                                        <span
                                            class="badge rounded-full bg-slate-600 px-3 py-1 text-sm font-bold text-white dark:bg-navy-500">{{ __('Unavailable') }}</span>
                                    </div>
                                @endif

                                @if($item->image_url)
                                    <img class="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        src="{{ $item->image_url }}" alt="{{ $item->name }}">
                                @else
                                    <div
                                        class="flex h-48 w-full items-center justify-center bg-slate-100 text-slate-400 dark:bg-navy-700 dark:text-navy-400">
                                        <svg class="size-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex flex-grow flex-col p-5">
                                    <div class="mb-2 flex items-start justify-between">
                                        <h5
                                            class="line-clamp-2 text-lg font-bold tracking-tight text-slate-800 dark:text-navy-100">
                                            {{ $item->name }}
                                        </h5>
                                        <span
                                            class="badge shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary dark:bg-accent-light/15 dark:text-accent-light">{{ number_format($item->price, 2) }}
                                            {{ __('SAR') }}</span>
                                    </div>
                                    <p class="mb-3 line-clamp-3 text-sm font-normal text-slate-600 dark:text-navy-300">
                                        {{ $item->description }}
                                    </p>

                                    <div class="mt-auto">
                                        <div class="mt-4 flex items-center justify-between">
                                            @if($item->max_per_request)
                                                <span class="text-xs font-medium text-warning">{{ __('Max') }}
                                                    {{ $item->max_per_request }} /{{ __('req') }}</span>
                                            @else
                                                <span></span>
                                            @endif

                                            <button type="button"
                                                onclick="openItemModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, {{ $item->max_per_request ?? 99 }})"
                                                class="btn bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus"
                                                {{ (!$item->is_active || !$capacityOn) ? 'disabled' : '' }}>
                                                {{ __('Add to Cart') }}
                                            </button>
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
                                    {{ __('SAR') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-navy-300">{{ __('Remaining') }}:</span>
                                <span
                                    class="font-bold {{ $remaining < 50 ? 'text-error' : 'text-success' }}">{{ number_format($remaining, 2) }}
                                    {{ __('SAR') }}</span>
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
                                        <span id="cart-total">0.00 {{ __('SAR') }}</span>
                                    </div>
                                    <div class="mt-1 flex justify-between text-xs text-slate-500 dark:text-navy-400">
                                        <span>{{ __('After Request') }}:</span>
                                        <span id="cart-projected-week" class="font-medium"></span>
                                    </div>
                                    <p id="allowance-warning" class="mt-2 hidden text-xs font-bold text-error">
                                        {{ __('Exceeds weekly allowance!') }}
                                    </p>
                                </div>

                                @error('allowance')
                                    <p class="mb-4 text-sm font-bold text-error">{{ $message }}</p>
                                @enderror
                                <form id="submit-request-form" action="{{ route('recipient.requests.store') }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="provider_id" value="{{ $provider->id }}">
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

    {{-- Item Selection Modal --}}
    <div id="item-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 left-0 right-0 top-0 z-50 flex h-[calc(100%-1rem)] max-h-full items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50 backdrop-blur-sm md:inset-0">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div
                class="relative rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-750">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-navy-600">
                    <h3 class="text-base font-semibold text-slate-800 dark:text-navy-100" id="modal-title">
                        {{ __('Select Item') }}
                    </h3>
                    <button type="button" onclick="closeItemModal()"
                        class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">{{ __('Close') }}</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <p class="mb-4 text-sm text-slate-500 dark:text-navy-400" id="modal-price"></p>
                    <div class="mb-4 flex items-center justify-between rounded-lg bg-slate-100 p-3 dark:bg-navy-600/50">
                        <span class="font-medium text-slate-700 dark:text-navy-100">{{ __('Quantity') }}</span>
                        <div class="flex items-center">
                            <button type="button" onclick="adjustModalQty(-1)"
                                class="btn size-8 flex items-center justify-center rounded-full bg-slate-200 font-bold text-slate-800 hover:bg-slate-300 dark:bg-navy-600 dark:text-navy-100 dark:hover:bg-navy-500">-</button>
                            <span id="modal-qty"
                                class="mx-4 text-lg font-bold text-slate-800 dark:text-navy-100">1</span>
                            <button type="button" onclick="adjustModalQty(1)"
                                class="btn size-8 flex items-center justify-center rounded-full bg-slate-200 font-bold text-slate-800 hover:bg-slate-300 dark:bg-navy-600 dark:text-navy-100 dark:hover:bg-navy-500">+</button>
                        </div>
                    </div>
                    <x-lineone-button type="button" onclick="addToCart()" variant="primary"
                        class="w-full">{{ __('Add to Request') }}</x-lineone-button>
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
                <p class="text-lg font-bold text-primary dark:text-accent-light" id="mobile-total">0.00 {{ __('SAR') }}
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
        let selectedItem = null;
        let selectedQty = 1;
        const weeklyUsed = {{ $weeklyUsed }};
        const allowance = {{ $weeklyLimit ?? 400 }};

        function openItemModal(id, name, price, max) {
            selectedItem = { id, name, price, max };
            selectedQty = 1;
            const existing = cart.find(i => i.id === id);
            if (existing) selectedQty = existing.qty;

            document.getElementById('modal-title').textContent = name;
            document.getElementById('modal-price').textContent = price.toFixed(2) + ' SAR / item';
            document.getElementById('modal-qty').textContent = selectedQty;
            document.getElementById('item-modal').classList.remove('hidden');
        }

        function closeItemModal() {
            document.getElementById('item-modal').classList.add('hidden');
            selectedItem = null;
        }

        function adjustModalQty(delta) {
            let newQty = selectedQty + delta;
            if (newQty < 1) newQty = 1;
            if (selectedItem.max && newQty > selectedItem.max) newQty = selectedItem.max;
            selectedQty = newQty;
            document.getElementById('modal-qty').textContent = selectedQty;
        }

        function addToCart() {
            if (!selectedItem) return;
            const existingIndex = cart.findIndex(i => i.id === selectedItem.id);
            if (existingIndex > -1) cart[existingIndex].qty = selectedQty;
            else cart.push({ ...selectedItem, qty: selectedQty });
            closeItemModal();
            renderCart();
        }

        function removeFromCart(id) {
            cart = cart.filter(i => i.id !== id);
            renderCart();
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
                    el.className = 'flex items-center justify-between rounded-lg bg-slate-100 p-2 text-sm dark:bg-navy-600/50';
                    el.innerHTML = `
                        <div>
                            <span class="block font-medium text-slate-700 dark:text-navy-100">${item.name}</span>
                            <span class="text-xs text-slate-500 dark:text-navy-400">${item.qty} x ${item.price.toFixed(2)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-700 dark:text-navy-100">${lineTotal.toFixed(2)}</span>
                            <button type="button" onclick="removeFromCart(${item.id})" class="text-error hover:text-error-focus">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    `;
                    container.appendChild(el);
                });
            }

            const projected = weeklyUsed + total;
            const exceeds = projected > allowance;

            document.getElementById('cart-total').textContent = total.toFixed(2) + ' SAR';
            document.getElementById('cart-projected-week').textContent = projected.toFixed(2) + ' / ' + allowance + ' SAR';
            document.getElementById('cart-projected-week').className = exceeds ? 'font-bold text-error' : 'font-medium text-success';

            const warningEl = document.getElementById('allowance-warning');
            const submitBtn = document.getElementById('submit-btn');
            const mobileSubmitBtn = document.getElementById('mobile-submit-btn');

            if (exceeds) {
                warningEl.classList.remove('hidden');
                submitBtn.disabled = true;
                mobileSubmitBtn.disabled = true;
            } else if (cart.length === 0) {
                warningEl.classList.add('hidden');
                submitBtn.disabled = true;
                mobileSubmitBtn.disabled = true;
            } else {
                warningEl.classList.add('hidden');
                submitBtn.disabled = false;
                mobileSubmitBtn.disabled = false;
            }

            document.getElementById('mobile-total').textContent = total.toFixed(2) + ' SAR';
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
        }
    </script>
</x-app-layout>