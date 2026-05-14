<?php

namespace App\Http\Requests\Admin;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->hasRole('admin')) {
            return false;
        }
        if ($this->has('roles') && ! $user->can('users.assign.roles')) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $guard = config('auth.defaults.guard', 'web');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'membership_type' => ['required', 'in:donor,recipient,provider,admin'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', $guard)],
        ];

        if (in_array($this->membership_type, ['donor', 'recipient', 'provider', 'admin'], true)) {
            $rules['phone_number'] = ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique];
        }

        if ($this->membership_type === 'recipient') {
            $rules = array_merge($rules, [
                'nationality' => ['required', 'string', 'in:'.implode(',', config('nationalities', []))],
                'short_address' => ['required', 'string', 'max:500'],
                'id_type' => ['required', 'string', 'in:'.implode(',', RecipientProfile::ID_TYPES)],
                'id_number' => ['required', 'digits:10', Rule::unique('recipient_profiles', 'id_number')],
                'id_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
                'income_band' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::INCOME_BANDS)],
                'household_size' => ['required', 'integer', 'min:1', 'max:50'],
                'marital_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::MARITAL_STATUSES)],
                'is_student' => ['required', 'boolean'],
                'employment_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::EMPLOYMENT_STATUSES)],
                'situation_description' => ['nullable', 'string', 'min:10', 'max:1000'],
            ]);
        }

        if ($this->membership_type === 'provider') {
            $rules = array_merge($rules, [
                'full_name_ar' => ['required', 'string', 'max:255'],
                'full_name_en' => ['required', 'string', 'max:255'],
                'business_name_ar' => ['required', 'string', 'max:255'],
                'business_name_en' => ['required', 'string', 'max:255'],
                'unified_number' => ['required', 'string', 'max:50'],
                'business_category' => ['required', 'array', 'min:1'],
                'business_category.*' => ['string', 'in:'.implode(',', config('provider.business_categories', []))],
                'address_ar' => ['required', 'string', 'max:1000'],
                'address_en' => ['required', 'string', 'max:1000'],
                'city' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.cities', [])))],
                'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.regions', [])))],
                'location' => ['nullable', 'string', 'max:500'],
                'daily_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
                'service_type' => ['required', 'array', 'min:1'],
                'service_type.*' => ['string', 'in:'.implode(',', config('provider.service_types', []))],
                'estimated_preparation_order_time' => ['required', 'string', 'max:50'],
                'adoption_support' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.adoption_support_options', [])))],
                'bank_name' => ['required', 'string', 'max:255'],
                'iban' => ['required', 'string', 'max:50'],
                'account_holder_name' => ['required', 'string', 'max:255'],
                'business_license' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'id_or_iqama' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]);
        }

        return $rules;
    }
}
