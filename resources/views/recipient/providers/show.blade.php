<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $provider->providerProfile->business_name_en ?? $provider->name }} - Menu
        </h2>
    </x-slot>

    <div class="py-12 pb-24 lg:pb-12"><!-- Extra padding for mobile bottom sheet -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 relative">
            <div class="lg:flex lg:gap-8">

                <!-- Left Column: Provider Info & Menu -->
                <div class="flex-1">
                    <!-- Provider Info Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-2xl font-bold mb-2">
                                    {{ $provider->providerProfile->business_name_en ?? $provider->name }}
                                </h1>
                                <p class="text-gray-600 mb-4">
                                    {{ $provider->providerProfile->business_category ? implode(', ', $provider->providerProfile->business_category) : 'General Provider' }}
                                </p>
                            </div>
                            <!-- Capacity Badge -->
                            @php
                                $capacityOn = $provider->providerOperatingInfo && $provider->providerOperatingInfo->daily_capacity > 0;
                            @endphp
                            <div>
                                @if($capacityOn)
                                    <span
                                        class="bg-green-100 text-green-800 text-sm font-medium mr-2 px-3 py-1 rounded dark:bg-green-900 dark:text-green-300">
                                        Capacity: ON
                                    </span>
                                @else
                                    <span
                                        class="bg-red-100 text-red-800 text-sm font-medium mr-2 px-3 py-1 rounded dark:bg-red-900 dark:text-red-300">
                                        Capacity: OFF
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $provider->providerProfile->location ?? ($provider->providerProfile->city . ', ' . $provider->providerProfile->region) }}
                            </p>
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Hours:
                                @if($provider->providerOperatingInfo && isset($provider->providerOperatingInfo->operating_hours[strtolower(now()->format('l'))]))
                                    @php $today = $provider->providerOperatingInfo->operating_hours[strtolower(now()->format('l'))]; @endphp
                                    {{ isset($today['closed']) && $today['closed'] ? 'Closed Today' : ($today['open'] . ' - ' . $today['close']) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Alerts -->
                    @if(Session::has('success'))
                        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                            <span class="font-medium">Success!</span> {{ Session::get('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Menu Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @forelse($menuItems as $item)
                            <div
                                class="bg-white border border-gray-200 rounded-lg shadow flex flex-col h-full hover:shadow-lg transition-shadow duration-200 overflow-hidden relative group">
                                @if(!$item->is_active)
                                    <div class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center">
                                        <span
                                            class="bg-gray-800 text-white px-3 py-1 rounded text-sm font-bold">Unavailable</span>
                                    </div>
                                @endif

                                @if($item->image_url)
                                    <img class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                        src="{{ $item->image_url }}" alt="{{ $item->name }}">
                                @else
                                    <div class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif

                                <div class="p-5 flex-grow flex flex-col">
                                    <div class="flex justify-between items-start mb-2">
                                        <h5 class="text-lg font-bold tracking-tight text-gray-900 line-clamp-2">
                                            {{ $item->name }}
                                        </h5>
                                        <span
                                            class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded whitespace-nowrap">{{ number_format($item->price, 2) }}
                                            SAR</span>
                                    </div>
                                    <p class="mb-3 font-normal text-gray-600 text-sm line-clamp-3">{{ $item->description }}
                                    </p>

                                    <div class="mt-auto">
                                        <div class="flex items-center justify-between mt-4">
                                            @if($item->max_per_request)
                                                <span class="text-xs text-orange-600 font-medium">Max
                                                    {{ $item->max_per_request }} /req</span>
                                            @else
                                                <span></span>
                                            @endif

                                            <button
                                                onclick="openItemModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, {{ $item->max_per_request ?? 99 }})"
                                                    class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    {{ (!$item->is_active || !$capacityOn) ? 'disabled' : '' }}>
                                                    Add to Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        @empty
                            <div class="col-span-full text-center py-12 bg-white rounded-lg shadow-sm">
                                <p class="text-gray-500 text-lg">No menu items found for this provider.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right Column: Cart & Summary (Sticky on Desktop) -->
                <div class="mt-8 lg:mt-0 lg:w-96">
                    <div class="sticky top-6">
                        <!-- Weekly Allowance Card -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Weekly Allowance</h3>

                            @php
                                $allowance = 400;
                                $remaining = max(0, $allowance - $weeklyUsed);
                                $percent = min(100, ($weeklyUsed / $allowance) * 100);
                                $color = $percent > 90 ? 'bg-red-600' : ($percent > 75 ? 'bg-orange-500' : 'bg-green-600');
                            @endphp

                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 dark:bg-gray-700">
                                <div class="{{ $color }} h-2.5 rounded-full transition-all duration-500"
                                    style="width: {{ $percent }}%"></div>
                            </div>

                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Used:</span>
                                <span class="font-bold text-gray-900">{{ number_format($weeklyUsed, 2) }} SAR</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Remaining:</span>
                                <span
                                    class="font-bold {{ $remaining < 50 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($remaining, 2) }}
                                    SAR</span>
                            </div>
                        </div>

                        <!-- Cart Summary (Desktop) -->
                        <div class="hidden lg:block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-blue-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Your Request</h3>

                            <div id="cart-items" class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                                <div class="text-center py-4 text-gray-500 text-sm">
                                    No items selected.
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <div class="flex justify-between font-bold text-gray-900">
                                    <span>Total:</span>
                                    <span id="cart-total">0.00 SAR</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>After Request:</span>
                                    <span id="cart-projected-week" class="font-medium"></span>
                                </div>
                                <p id="allowance-warning" class="text-xs text-red-600 mt-2 hidden font-bold">Exceeds
                                    weekly allowance!</p>
                            </div>

                            <form id="submit-request-form" action="{{ route('recipient.requests.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                <div id="form-items-container"></div>
                                
                                <button type="submit" id="submit-btn" disabled
                                    class="text-white w-full bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-sm px-5 py-3 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Selection Modal -->
    <div id="item-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-black/50 backdrop-blur-sm flex">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                        Select Item
                    </h3>
                    <button type="button" onclick="closeItemModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5">
                    <p class="text-sm text-gray-500 mb-4" id="modal-price"></p>
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded mb-4">
                        <span class="font-medium text-gray-900">Quantity</span>
                        <div class="flex items-center">
                            <button onclick="adjustModalQty(-1)" class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center hover:bg-gray-300 text-gray-800 font-bold">-</button>
                            <span id="modal-qty" class="mx-4 font-bold text-lg">1</span>
                            <button onclick="adjustModalQty(1)" class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center hover:bg-gray-300 text-gray-800 font-bold">+</button>
                        </div>
                    </div>
                    <button onclick="addToCart()" type="button" class="text-white w-full bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-bold rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Add to Request</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Cart Summary -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-4 lg:hidden">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-xs text-gray-500">Total Items: <span id="mobile-count" class="font-bold text-gray-900">0</span></p>
                <p class="font-bold text-lg text-blue-600" id="mobile-total">0.00 SAR</p>
            </div>
            <button onclick="document.getElementById('submit-btn').click()" id="mobile-submit-btn" disabled class="bg-blue-700 text-white font-bold py-2 px-6 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                Submit Request
            </button>
        </div>
    </div>

    <script>
        // State
        let cart = []; // [{id, name, price, qty, max}]
        let selectedItem = null;
        let selectedQty = 1;
        
        // Constants (from PHP)
        const weeklyUsed = {{ $weeklyUsed }};
        const allowance = 400;

        // logic
        function openItemModal(id, name, price, max) {
            selectedItem = { id, name, price, max };
            selectedQty = 1;
            
            // Check if already in cart
            const existing = cart.find(i => i.id === id);
            if (existing) {
                selectedQty = existing.qty;
            }

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
            if (existingIndex > -1) {
                cart[existingIndex].qty = selectedQty;
            } else {
                cart.push({
                    ...selectedItem,
                    qty: selectedQty
                });
            }
            
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
            
            let total = 0;
            let count = 0;
            
            if (cart.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">No items selected.</div>';
            } else {
                cart.forEach(item => {
                    const lineTotal = item.price * item.qty;
                    total += lineTotal;
                    count += item.qty;
                    
                    const el = document.createElement('div');
                    el.className = 'flex justify-between items-center text-sm bg-gray-50 p-2 rounded';
                    el.innerHTML = `
                        <div>
                            <span class="font-medium block">${item.name}</span>
                            <span class="text-gray-500 text-xs">${item.qty} x ${item.price.toFixed(2)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                             <span class="font-bold">${lineTotal.toFixed(2)}</span>
                             <button type="button" onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                             </button>
                        </div>
                    `;
                    container.appendChild(el);
                });
            }
            
            // Updates Totals
            const projected = weeklyUsed + total;
            const exceeds = projected > allowance;
            
            document.getElementById('cart-total').textContent = total.toFixed(2) + ' SAR';
            document.getElementById('cart-projected-week').textContent = projected.toFixed(2) + ' / ' + allowance + ' SAR';
            document.getElementById('cart-projected-week').className = exceeds ? 'font-bold text-red-600' : 'font-medium text-green-600';
            
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
            
            // Mobile
            document.getElementById('mobile-total').textContent = total.toFixed(2) + ' SAR';
            document.getElementById('mobile-count').textContent = count;
            
            // Update Hidden Inputs
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