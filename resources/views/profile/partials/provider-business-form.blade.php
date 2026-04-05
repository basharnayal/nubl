@php
    $phoneDisplay = $providerProfile->phone_number ?? '';
    if (str_starts_with($phoneDisplay, '966') && strlen($phoneDisplay) >= 12) {
        $phoneDisplay = substr($phoneDisplay, 3);
    }
    $businessCategories = config('provider.business_categories', []);
    $selectedCategories = old('business_category', $providerProfile->business_category ?? []);
    if (! is_array($selectedCategories)) {
        $selectedCategories = [];
    }
    $regions = config('provider.regions', []);
@endphp

<form method="POST" action="{{ route('profile.provider-business.update') }}" class="space-y-8">
    @csrf
    @method('PATCH')

    <section class="space-y-4">
        <div class="border-b border-slate-100 pb-2 dark:border-navy-600">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-navy-100">{{ __('Contact & representative') }}</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('Shown to recipients and used for account identification.') }}</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="full_name_ar" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Full Name (Arabic)') }}</label>
                <input type="text" name="full_name_ar" id="full_name_ar" value="{{ old('full_name_ar', $providerProfile->full_name_ar) }}" required
                    class="form-input form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
                @error('full_name_ar')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="full_name_en" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Full Name (English)') }}</label>
                <input type="text" name="full_name_en" id="full_name_en" value="{{ old('full_name_en', $providerProfile->full_name_en) }}" required
                    class="form-input form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
                @error('full_name_en')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <label for="phone_number" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Phone (Saudi)') }}</label>
            <input type="tel" name="phone_number" id="phone_number" maxlength="10" value="{{ old('phone_number', $phoneDisplay) }}" required dir="ltr"
                class="form-input form-input-lineone w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50"
                x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)" />
            <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('10 digits, e.g. 5XXXXXXXX') }}</p>
            @error('phone_number')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="space-y-4 border-t border-slate-100 pt-8 dark:border-navy-600">
        <div class="border-b border-slate-100 pb-2 dark:border-navy-600">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-navy-100">{{ __('Business identity') }}</h3>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-navy-600 dark:bg-navy-900/40">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('Unified commercial number') }}</p>
            <p class="mt-1 font-mono text-sm text-slate-800 dark:text-navy-100">{{ $providerProfile->unified_number }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-navy-400">{{ __('This number cannot be changed here. Contact support if it needs updating.') }}</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="business_name_ar" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Business Name (Arabic)') }}</label>
                <input type="text" name="business_name_ar" id="business_name_ar" value="{{ old('business_name_ar', $providerProfile->business_name_ar) }}" required
                    class="form-input form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
                @error('business_name_ar')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="business_name_en" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Business Name (English)') }}</label>
                <input type="text" name="business_name_en" id="business_name_en" value="{{ old('business_name_en', $providerProfile->business_name_en) }}" required
                    class="form-input form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
                @error('business_name_en')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <span class="mb-2 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Business category') }}</span>
            <div class="flex flex-wrap gap-3">
                @foreach ($businessCategories as $cat)
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-navy-500 dark:bg-navy-800/80">
                        <input type="checkbox" name="business_category[]" value="{{ $cat }}"
                            class="rounded border-slate-300 text-primary focus:ring-primary dark:border-navy-500 dark:text-accent-light"
                            @checked(in_array($cat, $selectedCategories, true)) />
                        <span>{{ __('provider.business_category.'.$cat) }}</span>
                    </label>
                @endforeach
            </div>
            @error('business_category')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="space-y-4 border-t border-slate-100 pt-8 dark:border-navy-600">
        <div class="border-b border-slate-100 pb-2 dark:border-navy-600">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-navy-100">{{ __('Location') }}</h3>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="address_ar" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Address (Arabic)') }}</label>
                <textarea name="address_ar" id="address_ar" rows="2" required
                    class="form-textarea form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50">{{ old('address_ar', $providerProfile->address_ar) }}</textarea>
                @error('address_ar')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address_en" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Address (English)') }}</label>
                <textarea name="address_en" id="address_en" rows="2" required
                    class="form-textarea form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50">{{ old('address_en', $providerProfile->address_en) }}</textarea>
                @error('address_en')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="city" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('City') }}</label>
                <select name="city" id="city" required
                    class="form-select form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50">
                    <option value="">— {{ __('Select') }} —</option>
                    @foreach (config('provider.cities', []) as $key => $label)
                        <option value="{{ $key }}" @selected(old('city', $providerProfile->city) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('city')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="region" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Region') }}</label>
                @if (count($regions) === 1)
                    <input type="hidden" name="region" value="{{ array_key_first($regions) }}">
                    <p class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 dark:border-navy-500 dark:bg-navy-900/50 dark:text-navy-100">{{ reset($regions) }}</p>
                @else
                    <select name="region" id="region" required
                        class="form-select form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50">
                        @foreach ($regions as $key => $label)
                            <option value="{{ $key }}" @selected(old('region', $providerProfile->region) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
                @error('region')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <label for="location" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Map or location note') }}</label>
            <input type="text" name="location" id="location" value="{{ old('location', $providerProfile->location) }}"
                placeholder="{{ __('Optional: link or short description') }}"
                class="form-input form-input-lineone w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
            @error('location')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6 dark:border-navy-600">
        <button type="submit"
            class="btn inline-flex min-h-[2.75rem] items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary dark:bg-accent dark:hover:bg-accent-focus">
            {{ __('Save business profile') }}
        </button>
        @if (session('status') === 'business-profile-updated')
            <span class="text-sm font-medium text-success">{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
