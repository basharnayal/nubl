<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if($requests->isEmpty())
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">You haven't made any requests yet.</p>
                            <a href="{{ route('recipient.providers.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Browse Providers
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3 px-6">ID</th>
                                        <th scope="col" class="py-3 px-6">Provider</th>
                                        <th scope="col" class="py-3 px-6">Date</th>
                                        <th scope="col" class="py-3 px-6">Items</th>
                                        <th scope="col" class="py-3 px-6">Total Amount</th>
                                        <th scope="col" class="py-3 px-6">Status</th>
                                        <th scope="col" class="py-3 px-6">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="bg-white border-b">
                                            <td class="py-4 px-6">#{{ $request->id }}</td>
                                            <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                                {{ $request->provider->name }}
                                            </td>
                                            <td class="py-4 px-6">
                                                {{ $request->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="py-4 px-6">
                                                {{ $request->items->sum('quantity') }} items
                                            </td>
                                            <td class="py-4 px-6">
                                                {{ $request->reserved_amount }} SAR
                                            </td>
                                            <td class="py-4 px-6">
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
                                                <span class="{{ $class }} text-xs font-medium px-2.5 py-0.5 rounded">
                                                    {{ str_replace('_', ' ', $request->status) }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <a href="{{ route('recipient.requests.show', $request->id) }}"
                                                    class="font-medium text-blue-600 hover:underline">View</a>
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
</x-app-layout>