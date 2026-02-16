<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $provider->providerProfile->business_name_en ?? $provider->name }} - Menu
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Provider Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                <h1 class="text-2xl font-bold mb-2">
                    {{ $provider->providerProfile->business_name_en ?? $provider->name }}
                </h1>
                <p class="text-gray-600 mb-4">
                    {{ $provider->providerProfile->business_category ? implode(', ', $provider->providerProfile->business_category) : 'General Provider' }}
                </p>

                <div class="text-sm text-gray-500">
                    <p class="flex items-center mb-1">
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
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                            </path>
                        </svg>
                        {{ $provider->providerProfile->phone_number }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" action="{{ route('recipient.providers.show', $provider->id) }}" class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            placeholder="Search menu...">
                    </div>
                    <div class="w-1/4">
                        <select name="category"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'category']))
                        <a href="{{ route('recipient.providers.show', $provider->id) }}"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Menu Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($menuItems as $item)
                    <div
                        class="bg-white border border-gray-200 rounded-lg shadow flex flex-col h-full hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                        @if($item->image_url)
                            <img class="w-full h-48 object-cover" src="{{ $item->image_url }}" alt="{{ $item->name }}">
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">
                                <span class="sr-only">No image available</span>
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5 flex-grow">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-xl font-bold tracking-tight text-gray-900">{{ $item->name }}</h5>
                                <span
                                    class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ number_format($item->price, 2) }}
                                    SAR</span>
                            </div>
                            <p class="mb-3 font-normal text-gray-700 text-sm">{{ $item->description }}</p>
                            @if($item->category)
                                <span
                                    class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded border border-gray-500">{{ $item->category }}</span>
                            @endif
                        </div>
                        <!-- Future: Add to Cart button -->
                        <div class="p-5 border-t border-gray-100 mt-auto">
                            <button
                                class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                                disabled title="Coming Soon">
                                Add to Order (Coming Soon)
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12">
                        <p class="text-gray-500 text-lg">No menu items found for this provider.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>