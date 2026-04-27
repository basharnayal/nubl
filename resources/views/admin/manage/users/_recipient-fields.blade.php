@php
    $profile = isset($user) ? ($user->recipientProfile ?? null) : null;
    $kyc = isset($user) ? ($user->recipientKycDetails ?? null) : null;
@endphp

{{-- Personal Information (name/email/phone: Account section) --}}
<div class="space-y-4">
    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-300">{{ __('Personal Information') }}</h4>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="nationality" :value="__('Nationality')" required />
            <select id="nationality" name="nationality" class="form-select form-select-lineone mt-1.5 w-full">
                <option value="">— {{ __('Select') }} —</option>
                @foreach($nationalities ?? config('nationalities', []) as $country)
                    <option value="{{ $country }}" {{ ($old['nationality'] ?? '') === $country ? 'selected' : '' }}>{{ $country }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="short_address" :value="__('Address')" required />
            <input id="short_address" type="text" name="short_address" value="{{ $old['short_address'] ?? '' }}" placeholder="{{ __('City - District - Street - House Number') }}" class="form-input form-input-lineone mt-1.5 w-full" />
            <x-input-error :messages="$errors->get('short_address')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="id_type" :value="__('ID Type')" required />
            <select id="id_type" name="id_type" class="form-select form-select-lineone mt-1.5 w-full">
                <option value="national_id" {{ ($old['id_type'] ?? '') === 'national_id' ? 'selected' : '' }}>{{ __('National ID') }}</option>
                <option value="iqama" {{ ($old['id_type'] ?? '') === 'iqama' ? 'selected' : '' }}>{{ __('Iqama') }}</option>
                <option value="hudood_number" {{ ($old['id_type'] ?? '') === 'hudood_number' ? 'selected' : '' }}>{{ __('Hudood Number (رقم الحدود)') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="id_number" :value="__('ID Number (رقم الهوية / الإقامة)')" required />
            <input id="id_number" type="text" name="id_number" value="{{ $old['id_number'] ?? '' }}" inputmode="numeric" maxlength="10" pattern="\d{10}" class="form-input form-input-lineone mt-1.5 w-full" />
            <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
        </div>
    </div>
</div>

<div class="space-y-4 border-t border-slate-200 pt-6 dark:border-navy-500">
    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-300">{{ __('KYC Details') }}</h4>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="income_band" :value="__('Income Band')" required />
            <select id="income_band" name="income_band" class="form-select form-select-lineone mt-1.5 w-full">
                @foreach(\App\Models\RecipientKycDetails::INCOME_BANDS as $band)
                    <option value="{{ $band }}" {{ ($old['income_band'] ?? '') === $band ? 'selected' : '' }}>{{ $band }} <x-sar-symbol /></option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="household_size" :value="__('Household Size')" required />
            <input id="household_size" type="number" name="household_size" value="{{ $old['household_size'] ?? 1 }}" min="1" max="50" class="form-input form-input-lineone mt-1.5 w-full" />
        </div>
        <div>
            <x-input-label for="marital_status" :value="__('Marital Status')" required />
            <select id="marital_status" name="marital_status" class="form-select form-select-lineone mt-1.5 w-full">
                @foreach(\App\Models\RecipientKycDetails::MARITAL_STATUSES as $s)
                    <option value="{{ $s }}" {{ ($old['marital_status'] ?? '') === $s ? 'selected' : '' }}>{{ __(ucfirst($s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="is_student" :value="__('Student')" required />
            <select id="is_student" name="is_student" class="form-select form-select-lineone mt-1.5 w-full">
                <option value="0" {{ ($old['is_student'] ?? '') === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                <option value="1" {{ ($old['is_student'] ?? '') === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="employment_status" :value="__('Employment Status')" required />
            <select id="employment_status" name="employment_status" class="form-select form-select-lineone mt-1.5 w-full">
                <option value="">— {{ __('Select') }} —</option>
                @foreach(\App\Models\RecipientKycDetails::EMPLOYMENT_STATUSES as $s)
                    <option value="{{ $s }}" {{ ($old['employment_status'] ?? '') === $s ? 'selected' : '' }}>{{ __(ucwords(str_replace('_', ' ', $s))) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="situation_description" :value="__('Situation Description (Optional)')" />
            <textarea id="situation_description" name="situation_description" rows="4" maxlength="1000" placeholder="{{ __('Describe the situation (10–1000 characters)') }}" class="form-input form-input-lineone mt-1.5 w-full resize-none">{{ $old['situation_description'] ?? '' }}</textarea>
            <x-input-error :messages="$errors->get('situation_description')" class="mt-2" />
        </div>
    </div>
</div>

<div class="space-y-4 border-t border-slate-200 pt-6 dark:border-navy-500">
    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-300">{{ __('Documents') }}</h4>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="id_photo" :value="__('ID Photo')" :required="$photosRequired ?? true" />
            @if(isset($user) && $profile?->id_photo_path)
                <a href="{{ route('admin.users.file', [$user, 'id_photo']) }}" target="_blank" class="mt-2 block">
                    <img src="{{ route('admin.users.file', [$user, 'id_photo']) }}" alt="{{ __('ID Photo') }}" class="max-h-64 max-w-full rounded-lg border object-contain" />
                </a>
            @endif
            <input id="id_photo" type="file" name="id_photo" accept="image/*" class="form-input form-input-lineone mt-2 w-full file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:hover:bg-primary-focus dark:file:bg-accent dark:file:hover:bg-accent-focus" {{ ($photosRequired ?? true) ? 'required' : '' }} />
            <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('Upload a new file to replace the current document.') }}</p>
            <x-input-error :messages="$errors->get('id_photo')" class="mt-2" />
        </div>
    </div>
</div>
