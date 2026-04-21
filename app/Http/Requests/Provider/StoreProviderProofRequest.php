<?php

namespace App\Http\Requests\Provider;

use App\Rules\Base64Image;
use Illuminate\Foundation\Http\FormRequest;

class StoreProviderProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('provider');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'proof_photo_base64' => ['nullable', 'string', new Base64Image],
        ];
    }
}
