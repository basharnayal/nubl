<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Request Queue') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if($requests->isEmpty())
                        <div class="text-center py-12">
                            <p class="text-gray-500">No pending requests requiring admin approval.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6">ID</th>
                                        <th class="py-3 px-6">Provider</th>
                                        <th class="py-3 px-6">Recipient</th>
                                        <th class="py-3 px-6">Amount</th>
                                        <th class="py-3 px-6">Date</th>
                                        <th class="py-3 px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="py-4 px-6">#{{ $request->id }}</td>
                                            <td class="py-4 px-6">{{ $request->provider->name }}</td>
                                            <td class="py-4 px-6">{{ $request->recipient->name }}</td>
                                            <td class="py-4 px-6 font-bold">{{ number_format($request->reserved_amount, 2) }}
                                                SAR</td>
                                            <td class="py-4 px-6">{{ $request->created_at->diffForHumans() }}</td>
                                            <td class="py-4 px-6">
                                                <button
                                                    onclick="openReviewModal({{ json_encode($request) }}, {{ json_encode($request->items) }})"
                                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                                    Review
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $requests->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-black/50 backdrop-blur-sm flex">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900" id="modal-title">
                        Review Request #<span id="modal-req-id"></span>
                    </h3>
                    <button type="button" onclick="closeReviewModal()"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 space-y-4">
                    <div id="modal-items-list" class="space-y-2 bg-gray-50 p-4 rounded max-h-60 overflow-y-auto">
                        <!-- Items injected here -->
                    </div>

                    <div class="flex justify-between items-center font-bold text-lg border-t pt-4">
                        <span>Total Amount:</span>
                        <span id="modal-total" class="text-blue-600"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <form id="approve-form" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="approve">
                            <button type="submit"
                                class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-bold rounded-lg text-sm px-5 py-2.5 text-center">
                                Approve (City Fund)
                            </button>
                        </form>

                        <button onclick="toggleRejectForm()"
                            class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-bold rounded-lg text-sm px-5 py-2.5 text-center">
                            Reject
                        </button>
                    </div>

                    <!-- Reject Form -->
                    <form id="reject-form" method="POST"
                        class="hidden mt-4 bg-red-50 p-4 rounded border border-red-100">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="reject">

                        <label class="block mb-2 text-sm font-medium text-red-900">Rejection Reason</label>
                        <select name="rejection_reason_code" required
                            class="bg-white border border-red-300 text-red-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 mb-2">
                            <option value="">Select Reason...</option>
                            <option value="Insufficient Funds">Insufficient Funds</option>
                            <option value="Policy Violation">Policy Violation</option>
                            <option value="Duplicate Request">Duplicate Request</option>
                            <option value="Other">Other</option>
                        </select>
                        <textarea name="rejection_reason_note" rows="2"
                            class="block p-2.5 w-full text-sm text-gray-900 bg-white rounded-lg border border-red-300 focus:ring-red-500 focus:border-red-500 mb-2"
                            placeholder="Notes (optional)"></textarea>

                        <button type="submit"
                            class="w-full text-white bg-red-800 hover:bg-red-900 font-medium rounded-lg text-sm px-5 py-2.5">
                            Confirm Rejection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openReviewModal(request, items) {
            document.getElementById('modal-req-id').textContent = request.id;
            document.getElementById('modal-total').textContent = parseFloat(request.reserved_amount).toFixed(2) + ' SAR';

            // Build Items
            const list = document.getElementById('modal-items-list');
            list.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'flex justify-between border-b border-gray-200 pb-2 last:border-0';
                // Need menu item name, usually loaded with relationship
                const itemName = item.menu_item ? item.menu_item.name : 'Item #' + item.menu_item_id;

                div.innerHTML = `
                    <div>
                        <span class="block font-medium">${itemName}</span>
                        <span class="text-xs text-gray-500">Qty: ${item.quantity}</span>
                    </div>
                    <span class="font-bold">${(item.price_snapshot * item.quantity).toFixed(2)}</span>
                `;
                list.appendChild(div);
            });

            // Set Action URLs
            const url = "{{ route('admin.requests.update', ':id') }}".replace(':id', request.id);
            document.getElementById('approve-form').action = url;
            document.getElementById('reject-form').action = url;

            // Reset UI
            document.getElementById('reject-form').classList.add('hidden');

            document.getElementById('review-modal').classList.remove('hidden');
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }

        function toggleRejectForm() {
            document.getElementById('reject-form').classList.toggle('hidden');
        }
    </script>
</x-app-layout>