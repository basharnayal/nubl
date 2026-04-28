<?php

namespace App\Http\Requests;

use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use App\Support\PhoneHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderBusinessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('provider') && $user->providerProfile !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'full_name_ar' => ['required', 'string', 'max:255'],
            'full_name_en' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique($user->id)],
            'business_name_ar' => ['required', 'string', 'max:255'],
            'business_name_en' => ['required', 'string', 'max:255'],
            'business_category' => ['required', 'array', 'min:1'],
            'business_category.*' => ['string', 'in:'.implode(',', config('provider.business_categories'))],
            'address_ar' => ['required', 'string', 'max:1000'],
            'address_en' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.cities', [])))],
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.regions', [])))],
            'location' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array{full_name_ar: string, full_name_en: string, business_name_ar: string, business_name_en: string, business_category: array, address_ar: string, address_en: string, city: string, region: string, location: ?string, phone_normalized: string}
     */
    public function businessProfilePayload(): array
    {
        $validated = $this->validated();
        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        return [
            'full_name_ar' => $validated['full_name_ar'],
            'full_name_en' => $validated['full_name_en'],
            'business_name_ar' => $validated['business_name_ar'],
            'business_name_en' => $validated['business_name_en'],
            'business_category' => $validated['business_category'],
            'address_ar' => $validated['address_ar'],
            'address_en' => $validated['address_en'],
            'city' => $validated['city'],
            'region' => $validated['region'],
            'location' => $validated['location'] ?? null,
            'phone_normalized' => $phoneNormalized,
        ];
    }
}
