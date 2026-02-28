<x-app-layout title="{{ __('Create Menu Item') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-2xl">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('Create Menu Item') }}
                </h2>

                <div class="card mt-3 p-6">
                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                            <ul class="list-disc list-inside text-sm text-error dark:text-error">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('provider.menu-items.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="space-y-4">
                            <label for="name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Item Name') }} *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="form-input form-input-lineone">

                            <div>
                                <label for="image" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Image') }} ({{ __('Optional') }})</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                    class="form-input form-input-lineone file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:hover:bg-primary-focus dark:file:bg-accent dark:file:hover:bg-accent-focus">
                                <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('SVG, PNG, JPG or GIF (MAX. 2MB).') }}</p>
                            </div>

                            <div>
                                <label for="description" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Description') }}</label>
                                <textarea id="description" name="description" rows="3"
                                    class="form-textarea form-textarea-lineone">{{ old('description') }}</textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="price" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Price') }} *</label>
                                    <input type="number" step="0.01" id="price" name="price" value="{{ old('price') }}" required
                                        class="form-input form-input-lineone">
                                </div>
                                <div>
                                    <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Category') }} *</label>
                                    <select id="category_id" name="category_id" required
                                        class="form-select form-select-lineone w-full ltr:pl-3 ltr:pr-9 rtl:pr-3 rtl:pl-10 rtl:bg-[position:left_0.5rem_center]"
                                        onfocus="if (typeof TomSelect !== 'undefined' && !this.tomselect) new TomSelect(this, {create: false})">
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="sku" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('SKU') }} ({{ __('Optional') }})</label>
                                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}"
                                        class="form-input form-input-lineone">
                                </div>
                                <div>
                                    <label for="max_per_request" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Max Per Request') }}</label>
                                    <input type="number" id="max_per_request" name="max_per_request" value="{{ old('max_per_request') }}"
                                        class="form-input form-input-lineone">
                                </div>
                            </div>

                            <label class="inline-flex items-center space-x-2">
                                <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                    class="form-checkbox is-basic size-5 rounded border-slate-400/70 checked:bg-primary checked:border-primary hover:border-primary focus:border-primary dark:border-navy-400 dark:checked:bg-accent dark:checked:border-accent dark:hover:border-accent dark:focus:border-accent">
                                <span class="text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Available for ordering') }}</span>
                            </label>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-lineone-button type="submit" variant="primary">{{ __('Create Item') }}</x-lineone-button>
                            <x-lineone-button :href="route('provider.menu-items.index')" variant="slate" outline>{{ __('Cancel') }}</x-lineone-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
