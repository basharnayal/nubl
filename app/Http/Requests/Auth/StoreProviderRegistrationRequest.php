<?php

namespace App\Http\Requests\Auth;

use App\Helpers\PhoneHelper;
use App\Models\User;
use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use App\Support\OperatingHoursNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class StoreProviderRegistrationRequest extends FormRequest
{
    /**
     * @var array<string, array{open?: string, close?: string, closed: bool}>|null
     */
    protected ?array $normalizedOperatingHours = null;

    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone_number');
        if (! is_string($phone) || $phone === '') {
            return;
        }
        if (PhoneHelper::isValid($phone)) {
            $this->merge([
                'phone_number' => PhoneHelper::nationalMobileDigits($phone),
            ]);
        }
    }

    public function rules(): array
    {
        $maxMb = config('provider.document_max_size_mb', 5);
        $maxBytes = $maxMb * 1024 * 1024;

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
            'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'business_name_ar' => ['required', 'string', 'max:255'],
            'business_name_en' => ['required', 'string', 'max:255'],
            'unified_number' => ['required', 'string', 'max:50'],
            'business_category' => ['required', 'array'],
            'business_category.*' => ['string', 'in:'.implode(',', config('provider.business_categories', []))],
            'address_ar' => ['required', 'string', 'max:1000'],
            'address_en' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.cities', [])))],
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.regions', [])))],
            'location' => ['nullable', 'string', 'max:500'],
            'profile_logo' => ['nullable', 'image', 'max:2048'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'service_type' => ['required', 'array'],
            'service_type.*' => ['string', 'in:'.implode(',', config('provider.service_types', []))],
            'estimated_preparation_order_time' => ['required', 'string', 'max:100'],
            'adoption_support' => ['required', 'string', 'in:yes,partially,no'],
            'bank_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:50'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'password' => ['required', Rules\Password::defaults()],
            'business_license' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
            'id_or_iqama' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
        ], $operatingHoursRules);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
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
