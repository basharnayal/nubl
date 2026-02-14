<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="full_name_ar" :value="__('Full Name (Arabic)')" required />
        <input id="full_name_ar" type="text" name="full_name_ar" value="{{ $old['full_name_ar'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
    <div>
        <x-input-label for="full_name_en" :value="__('Full Name (English)')" required />
        <input id="full_name_en" type="text" name="full_name_en" value="{{ $old['full_name_en'] ?? $old['name'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="business_name_ar" :value="__('Business Name (Arabic)')" required />
        <input id="business_name_ar" type="text" name="business_name_ar" value="{{ $old['business_name_ar'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
    <div>
        <x-input-label for="business_name_en" :value="__('Business Name (English)')" required />
        <input id="business_name_en" type="text" name="business_name_en" value="{{ $old['business_name_en'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
</div>
<div>
    <x-input-label for="unified_number" :value="__('Unified Number')" required />
    <input id="unified_number" type="text" name="unified_number" value="{{ $old['unified_number'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
</div>
<div>
    <x-input-label :value="__('Business Category')" required />
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach($businessCategories ?? [] as $cat)
            <label class="inline-flex items-center">
                <input type="checkbox" name="business_category[]" value="{{ $cat }}" {{ in_array($cat, $old['business_category'] ?? []) ? 'checked' : '' }} class="rounded border-gray-300">
                <span class="ms-2 text-sm">{{ ucfirst(str_replace('_', ' ', $cat)) }}</span>
            </label>
        @endforeach
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="address_ar" :value="__('Address (Arabic)')" required />
        <textarea id="address_ar" name="address_ar" rows="2" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">{{ $old['address_ar'] ?? '' }}</textarea>
    </div>
    <div>
        <x-input-label for="address_en" :value="__('Address (English)')" required />
        <textarea id="address_en" name="address_en" rows="2" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">{{ $old['address_en'] ?? '' }}</textarea>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="city" :value="__('City')" required />
        <select id="city" name="city" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">
            @foreach(config('provider.cities', []) as $key => $label)
                <option value="{{ $key }}" {{ ($old['city'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="region" :value="__('Region')" required />
        <select id="region" name="region" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">
            @foreach(config('provider.regions', []) as $key => $label)
                <option value="{{ $key }}" {{ ($old['region'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <x-input-label for="location" :value="__('Location (optional)')" />
    <input id="location" type="text" name="location" value="{{ $old['location'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
</div>
<div>
    <x-input-label for="daily_capacity" :value="__('Daily Capacity')" required />
    <input id="daily_capacity" type="number" name="daily_capacity" value="{{ $old['daily_capacity'] ?? 50 }}" min="1" max="10000" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
</div>
<div>
    <x-input-label :value="__('Service Type')" required />
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach($serviceTypes ?? [] as $s)
            <label class="inline-flex items-center">
                <input type="checkbox" name="service_type[]" value="{{ $s }}" {{ in_array($s, $old['service_type'] ?? []) ? 'checked' : '' }} class="rounded border-gray-300">
                <span class="ms-2 text-sm">{{ ucfirst(str_replace('_', ' ', $s)) }}</span>
            </label>
        @endforeach
    </div>
</div>
<div>
    <x-input-label for="estimated_preparation_order_time" :value="__('Preparation Time')" required />
    <select id="estimated_preparation_order_time" name="estimated_preparation_order_time" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">
        @foreach(['15 minutes','30 minutes','45 minutes','1 hour','1.5 hours','2 hours'] as $t)
            <option value="{{ $t }}" {{ ($old['estimated_preparation_order_time'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>
</div>
<div>
    <x-input-label for="adoption_support" :value="__('Adoption Support')" required />
    <select id="adoption_support" name="adoption_support" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm">
        @foreach(config('provider.adoption_support_options', []) as $key => $label)
            <option value="{{ $key }}" {{ ($old['adoption_support'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="bank_name" :value="__('Bank Name')" required />
        <input id="bank_name" type="text" name="bank_name" value="{{ $old['bank_name'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
    <div>
        <x-input-label for="iban" :value="__('IBAN')" required />
        <input id="iban" type="text" name="iban" value="{{ $old['iban'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
    </div>
</div>
<div>
    <x-input-label for="account_holder_name" :value="__('Account Holder')" required />
    <input id="account_holder_name" type="text" name="account_holder_name" value="{{ $old['account_holder_name'] ?? '' }}" class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm" />
</div>
<div>
    <x-input-label for="business_license" :value="__('Business License')" :required="$docsRequired ?? true" />
    <input id="business_license" type="file" name="business_license" accept=".pdf,.jpg,.jpeg,.png" class="block mt-1 w-full" {{ ($docsRequired ?? true) ? 'required' : '' }} />
</div>
<div>
    <x-input-label for="id_or_iqama" :value="__('ID / Iqama')" :required="$docsRequired ?? true" />
    <input id="id_or_iqama" type="file" name="id_or_iqama" accept=".pdf,.jpg,.jpeg,.png" class="block mt-1 w-full" {{ ($docsRequired ?? true) ? 'required' : '' }} />
</div>
