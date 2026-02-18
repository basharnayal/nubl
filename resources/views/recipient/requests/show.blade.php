<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Request Details') }} #{{ $request->id }}
            </h2>
            <a href="{{ route('recipient.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700"> Back to
                List</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            @php
                                $classes = [
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'ADOPTED' => 'bg-purple-100 text-purple-800',
                                    'PROVIDER_APPROVED' => 'bg-blue-100 text-blue-800',
                                    'ADMIN_PENDING' => 'bg-orange-100 text-orange-800',
                                    'ADMIN_APPROVED' => 'bg-green-100 text-green-800',
                                    'REDEEMABLE' => 'bg-green-100 text-green-800',
                                    'FULFILLED' => 'bg-gray-100 text-gray-800',
                                    'PROVIDER_REJECTED' => 'bg-red-100 text-red-800',
                                    'ADMIN_REJECTED' => 'bg-red-100 text-red-800',
                                ];
                                $class = $classes[$request->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="{{ $class }} text-lg font-bold px-3 py-1 rounded mt-1 inline-block">
                                {{ str_replace('_', ' ', $request->status) }}
                            </span>
                        </div>

                        <div class="text-right">
                            @if(in_array($request->status, ['ADOPTED', 'PROVIDER_APPROVED', 'ADMIN_APPROVED', 'REDEEMABLE']))
                                <button onclick="alert('QR Code generation coming soon!')"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 6h6v6H6V6zm2 2v2h2V8H8zm8-2h6v6h-6V6zm2 2v2h2V8h-2zM6 18h6v6H6v-6zm2 2v2h2v-2H8z">
                                        </path>
                                    </svg>
                                    View QR Code
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($request->rejection_reason_code)
                        <div class="mt-4 p-4 bg-red-50 rounded-lg text-red-800">
                            <p class="font-bold">Reason for Rejection:</p>
                            <p>{{ $request->rejection_reason_code }}</p>
                            @if($request->rejection_reason_note)
                                <p class="mt-1 text-sm">{{ $request->rejection_reason_note }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Items List -->
                <div class="md:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Request Items</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6">Item</th>
                                        <th class="py-3 px-6">Quantity</th>
                                        <th class="py-3 px-6">Price</th>
                                        <th class="py-3 px-6">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($request->items as $item)
                                        <tr class="bg-white border-b">
                                            <td class="py-4 px-6 font-medium text-gray-900">
                                                {{ $item->menuItem->name ?? 'Unknown Item' }}
                                            </td>
                                            <td class="py-4 px-6">{{ $item->quantity }}</td>
                                            <td class="py-4 px-6">{{ $item->price_snapshot }} SAR</td>
                                            <td class="py-4 px-6 font-bold">
                                                {{ number_format($item->price_snapshot * $item->quantity, 2) }} SAR</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50 font-bold">
                                        <td colspan="3" class="py-4 px-6 text-right">Grand Total</td>
                                        <td class="py-4 px-6">{{ number_format($request->reserved_amount, 2) }} SAR</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Provider Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg h-fit">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Provider Info</h3>
                        <p class="text-gray-900 font-medium">{{ $request->provider->name }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $request->provider->providerProfile->location ?? 'Location N/A' }}
                        </p>

                        <div class="mt-6 pt-6 border-t">
                            <p class="text-xs text-gray-400">Request ID: {{ $request->id }}</p>
                            <p class="text-xs text-gray-400">Date: {{ $request->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>