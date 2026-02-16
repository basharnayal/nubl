<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('provider');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'sku' => 'nullable|string|max:100',
            'max_per_request' => 'nullable|integer|min:1|max:999',
            'is_active' => 'boolean',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
