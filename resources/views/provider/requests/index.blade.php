<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Incoming Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if($requests->isEmpty())
                        <div class="text-center py-12">
                            <p class="text-gray-500">No requests found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6">ID</th>
                                        <th class="py-3 px-6">Recipient</th>
                                        <th class="py-3 px-6">Items</th>
                                        <th class="py-3 px-6">Total</th>
                                        <th class="py-3 px-6">Date</th>
                                        <th class="py-3 px-6">Status</th>
                                        <th class="py-3 px-6">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="py-4 px-6">#{{ $request->id }}</td>
                                            <td class="py-4 px-6 font-medium text-gray-900">
                                                {{ $request->recipient->name }}
                                            </td>
                                            <td class="py-4 px-6">
                                                {{ $request->items->sum('quantity') }} items
                                            </td>
                                            <td class="py-4 px-6 font-bold">
                                                {{ $request->reserved_amount }} SAR
                                            </td>
                                            <td class="py-4 px-6">
                                                {{ $request->created_at->diffForHumans() }}
                                            </td>
                                            <td class="py-4 px-6">
                                                @if($request->status === 'PENDING')
                                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Pending Action</span>
                                                @else
                                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $request->status }}</span>
                                                @endif
                                            </td>
                                            <td class="py-4 px-6">
                                                <a href="{{ route('provider.requests.show', $request->id) }}" class="font-medium text-blue-600 hover:underline">Review</a>
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
