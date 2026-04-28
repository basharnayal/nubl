<?php

namespace App\Http\Requests\Auth;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Rules\Base64Image;
use App\Support\OperatingHoursNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UpdateResubmitApplicationRequest extends FormRequest
{
    /**
     * @var array<string, array{open?: string, close?: string, closed: bool}>|null
     */
    protected ?array $normalizedOperatingHours = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }
        if ($user->status !== User::STATUS_REJECTED) {
            throw new HttpResponseException(redirect()->route('approval.pending'));
        }
        if (! in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)) {
            abort(403);
        }

        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'nationality' => ['required', 'string', 'in:'.implode(',', config('nationalities', []))],
                'short_address' => ['required', 'string', 'max:500'],
                'id_type' => ['required', 'string', 'in:'.implode(',', RecipientProfile::ID_TYPES)],
                'id_number' => ['required', 'digits:10'],
                'income_band' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::INCOME_BANDS)],
                'household_size' => ['required', 'integer', 'min:1', 'max:50'],
                'marital_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::MARITAL_STATUSES)],
                'is_student' => ['required', 'in:0,1'],
                'employment_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::EMPLOYMENT_STATUSES)],
                'situation_description' => ['required', 'string', 'min:10', 'max:1000'],
                'id_photo_base64' => ['nullable', 'string'],
            ];
        }

        $maxMb = config('provider.document_max_size_mb', 5);
        $maxKilobytes = $maxMb * 1024;

        $weekdays = array_keys(config('provider.weekdays'));
        $operatingHoursRules = [
            'operating_hours' => ['required', 'array'],
        ];
        foreach ($weekdays as $day) {
            $operatingHoursRules["operating_hours.{$day}"] = ['required', 'array'];
        }

        return array_merge([
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
            'estimated_preparation_order_time' => ['required', 'string', 'max:100'],
            'adoption_support' => ['required', 'string', 'in:yes,partially,no'],
            'bank_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:50'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'business_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKilobytes],
            'id_or_iqama' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKilobytes],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], $operatingHoursRules);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
                if ($this->filled('id_photo_base64')) {
                    $v = Validator::make(
                        ['id_photo_base64' => $this->input('id_photo_base64')],
                        ['id_photo_base64' => [new Base64Image]]
                    );
                    if ($v->fails()) {
                        foreach ($v->errors()->all() as $message) {
                            $validator->errors()->add('id_photo_base64', $message);
                        }
                    }
                }

                return;
            }

            try {
                $this->normalizedOperatingHours = OperatingHoursNormalizer::fromRequest($this);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }

    /**
     * @return array<string, array{open?: string, close?: string, closed: bool}>
     */
    public function normalizedOperatingHours(): array
    {
        return $this->normalizedOperatingHours ?? OperatingHoursNormalizer::fromRequest($this);
    }
}
