<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Review Request') }} #{{ $request->id }}
            </h2>
            <a href="{{ route('provider.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to
                List</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left: Request Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4">Ordered Items</h3>
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6">Item</th>
                                        <th class="py-3 px-6">Qty</th>
                                        <th class="py-3 px-6">Price</th>
                                        <th class="py-3 px-6">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($request->items as $item)
                                        <tr class="bg-white border-b">
                                            <td class="py-4 px-6 font-medium text-gray-900">
                                                {{ $item->menuItem->name ?? 'Unknown' }}
                                            </td>
                                            <td class="py-4 px-6">{{ $item->quantity }}</td>
                                            <td class="py-4 px-6">{{ $item->price_snapshot }}</td>
                                            <td class="py-4 px-6 font-bold">
                                                {{ number_format($item->price_snapshot * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50 font-bold">
                                        <td colspan="3" class="py-4 px-6 text-right">Grand Total</td>
                                        <td class="py-4 px-6 text-lg">{{ number_format($request->reserved_amount, 2) }}
                                            SAR</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4">Recipient Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-gray-500 text-xs uppercase">Name</span>
                                    <span class="font-medium">{{ $request->recipient->name }}</span>
                                </div>
                                <div>
                                    <span class="block text-gray-500 text-xs uppercase">Request Date</span>
                                    <span>{{ $request->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <!-- Add more details if allowed by privacy/specs -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-6">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4">Actions</h3>

                            @if($request->status === 'PENDING')
                                <div class="space-y-4">
                                    <!-- Adopt Action -->
                                    <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="adopt">
                                        <button type="submit"
                                            class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-3 mb-2 focus:outline-none">
                                            Adopt Request (My Fund)
                                        </button>
                                        <p class="text-xs text-gray-500 text-center mb-4">
                                            You will cover the cost of this request.
                                        </p>
                                    </form>

                                    <hr class="border-gray-200 my-4">

                                    <!-- Approve Action -->
                                    <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit"
                                            class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 mb-2 focus:outline-none">
                                            Approve (City Fund)
                                        </button>
                                        <p class="text-xs text-gray-500 text-center mb-4">
                                            Request will be paid from the City Fund.
                                        </p>
                                    </form>

                                    <hr class="border-gray-200 my-4">

                                    <!-- Reject Action -->
                                    <button onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                                        class="w-full text-red-600 border border-red-600 hover:bg-red-50 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-3 mb-2 focus:outline-none">
                                        Reject Request
                                    </button>

                                    <form id="reject-form" action="{{ route('provider.requests.update', $request->id) }}"
                                        method="POST" class="hidden mt-4 bg-red-50 p-4 rounded-lg">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="reject">

                                        <label for="reason"
                                            class="block mb-2 text-sm font-medium text-gray-900">Reason</label>
                                        <select id="reason" name="rejection_reason_code" required
                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 mb-3">
                                            <option value="">Select a reason...</option>
                                            <option value="Item Unavailable">Item Unavailable</option>
                                            <option value="Capacity Full">Capacity Full</option>
                                            <option value="Closing Soon">Closing Soon</option>
                                            <option value="Other">Other</option>
                                        </select>

                                        <textarea name="rejection_reason_note" rows="2"
                                            class="block p-2.5 w-full text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-red-500 focus:border-red-500 mb-3"
                                            placeholder="Additional notes (optional)"></textarea>

                                        <button type="submit"
                                            class="w-full text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                            Confirm Rejection
                                        </button>
                                    </form>

                                </div>
                            @else
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <span class="block font-bold text-gray-700">Request {{ $request->status }}</span>
                                    <span class="text-sm text-gray-500">No further actions available.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>