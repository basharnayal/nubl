<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Menu Item') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 p-4 rounded-lg">
                        <ul class="list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('provider.menu-items.update', $menuItem->id) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div class="mb-4">
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Item Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $menuItem->name) }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    <!-- Image -->
                    <div class="mb-4">
                        <label for="image" class="block mb-2 text-sm font-medium text-gray-900">Image
                            (Optional)</label>
                        @if($menuItem->image_url)
                            <div class="mb-2">
                                <img src="{{ $menuItem->image_url }}" alt="Current Image"
                                    class="h-20 w-20 object-cover rounded">
                            </div>
                        @endif
                        <input type="file" id="image" name="image" accept="image/*"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        <p class="mt-1 text-sm text-gray-500">SVG, PNG, JPG or GIF (MAX. 2MB).</p>
                    </div>


                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description"
                            class="block mb-2 text-sm font-medium text-gray-900">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">{{ old('description', $menuItem->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Price -->
                        <div>
                            <label for="price" class="block mb-2 text-sm font-medium text-gray-900">Price *</label>
                            <input type="number" step="0.01" id="price" name="price"
                                value="{{ old('price', $menuItem->price) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block mb-2 text-sm font-medium text-gray-900">Category
                                *</label>
                            <input type="text" id="category" name="category"
                                value="{{ old('category', $menuItem->category) }}" required list="categories-list"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <datalist id="categories-list">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- SKU -->
                        <div>
                            <label for="sku" class="block mb-2 text-sm font-medium text-gray-900">SKU (Optional)</label>
                            <input type="text" id="sku" name="sku" value="{{ old('sku', $menuItem->sku) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>

                        <!-- Max Per Request -->
                        <div>
                            <label for="max_per_request" class="block mb-2 text-sm font-medium text-gray-900">Max Per
                                Request</label>
                            <input type="number" id="max_per_request" name="max_per_request"
                                value="{{ old('max_per_request', $menuItem->max_per_request) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center mb-6">
                        <input id="is_active-hidden" type="hidden" name="is_active" value="0">
                        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $menuItem->is_active) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-900">Available for
                            ordering</label>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Update
                            Item</button>
                        <a href="{{ route('provider.menu-items.index') }}"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>