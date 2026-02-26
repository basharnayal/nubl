<div>
    <x-input-label for="nationality" :value="__('Nationality')" required />
    <select id="nationality" name="nationality" class="form-select form-select-lineone mt-1.5">
        <option value="">— {{ __('Select') }} —</option>
        @foreach($nationalities ?? config('nationalities', []) as $country)
            <option value="{{ $country }}" {{ ($old['nationality'] ?? '') === $country ? 'selected' : '' }}>{{ $country }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
</div>
<div>
    <x-input-label for="short_address" :value="__('Short Address')" required />
    <input id="short_address" type="text" name="short_address" value="{{ $old['short_address'] ?? '' }}" class="form-input form-input-lineone mt-1.5" />
    <x-input-error :messages="$errors->get('short_address')" class="mt-2" />
</div>
<div>
    <x-input-label for="id_type" :value="__('ID Type')" required />
    <select id="id_type" name="id_type" class="form-select form-select-lineone mt-1.5">
        <option value="national_id" {{ ($old['id_type'] ?? '') === 'national_id' ? 'selected' : '' }}>{{ __('National ID') }}</option>
        <option value="iqama" {{ ($old['id_type'] ?? '') === 'iqama' ? 'selected' : '' }}>{{ __('Iqama') }}</option>
    </select>
</div>
<div>
    <x-input-label for="id_photo" :value="__('ID Photo')" :required="$photosRequired ?? true" />
    <input id="id_photo" type="file" name="id_photo" accept="image/*" class="form-input form-input-lineone mt-1.5 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:hover:bg-primary-focus dark:file:bg-accent dark:file:hover:bg-accent-focus" {{ ($photosRequired ?? true) ? 'required' : '' }} />
    <x-input-error :messages="$errors->get('id_photo')" class="mt-2" />
</div>
<div>
    <x-input-label for="income_band" :value="__('Income Band')" required />
    <select id="income_band" name="income_band" class="form-select form-select-lineone mt-1.5">
        @foreach(\App\Models\RecipientKycDetails::INCOME_BANDS as $band)
            <option value="{{ $band }}" {{ ($old['income_band'] ?? '') === $band ? 'selected' : '' }}>{{ $band }} SAR</option>
        @endforeach
    </select>
</div>
<div>
    <x-input-label for="household_size" :value="__('Household Size')" required />
    <input id="household_size" type="number" name="household_size" value="{{ $old['household_size'] ?? 1 }}" min="1" max="50" class="form-input form-input-lineone mt-1.5" />
</div>
<div>
    <x-input-label for="marital_status" :value="__('Marital Status')" required />
    <select id="marital_status" name="marital_status" class="form-select form-select-lineone mt-1.5">
        @foreach(\App\Models\RecipientKycDetails::MARITAL_STATUSES as $s)
            <option value="{{ $s }}" {{ ($old['marital_status'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
</div>
<div>
    <x-input-label for="is_student" :value="__('Student')" required />
    <select id="is_student" name="is_student" class="form-select form-select-lineone mt-1.5">
        <option value="0" {{ ($old['is_student'] ?? '') === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
        <option value="1" {{ ($old['is_student'] ?? '') === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
    </select>
</div>
<div>
    <x-input-label for="address_confirmation" :value="__('Address Confirmation Photo')" :required="$photosRequired ?? true" />
    <input id="address_confirmation" type="file" name="address_confirmation" accept="image/*" class="form-input form-input-lineone mt-1.5 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:hover:bg-primary-focus dark:file:bg-accent dark:file:hover:bg-accent-focus" {{ ($photosRequired ?? true) ? 'required' : '' }} />
    <x-input-error :messages="$errors->get('address_confirmation')" class="mt-2" />
</div>
