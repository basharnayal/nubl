<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('name_ar') && trim((string) $this->input('name_ar')) === '') {
            $this->merge(['name_ar' => null]);
        }
        if ($this->has('description_ar') && trim((string) $this->input('description_ar')) === '') {
            $this->merge(['description_ar' => null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->hasRole('provider');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'category_id' => [
                'required',
                'exists:menu_item_categories,id',
                function ($attribute, $value, $fail) {
                    $provider = $this->user();
                    $profile = $provider->providerProfile;
                    $businessCategories = $profile?->business_category ?? ['Other'];
                    $businessCategory = is_array($businessCategories) && count($businessCategories) > 0
                        ? $businessCategories[0]
                        : (is_string($businessCategories) ? $businessCategories : 'Other');

                    if (strtolower($businessCategory) !== 'other') {
                        $category = \App\Models\MenuItemCategory::find($value);
                        if ($category && strtolower($category->business_category) !== strtolower($businessCategory) && strtolower($category->business_category) !== 'other') {
                            $fail('The selected category does not match your business category.');
                        }
                    }
                },
            ],
            'category' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'sku' => 'nullable|string|max:100',
            'max_per_request' => 'nullable|integer|min:1|max:999',
            'is_active' => 'boolean',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
