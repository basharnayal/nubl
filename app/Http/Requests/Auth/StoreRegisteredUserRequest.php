<?php

namespace App\Http\Requests\Auth;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Rules\Base64Image;
use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreRegisteredUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        $rules = [
            'membership_type' => ['required', 'in:donor,recipient'],
        ];

        $type = $this->input('membership_type');

        if ($type === User::MEMBERSHIP_DONOR) {
            return array_merge($rules, [
                'name'         => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
                'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password'     => ['required', Rules\Password::defaults()],
            ]);
        }

        if ($type === User::MEMBERSHIP_RECIPIENT) {
            return array_merge($rules, [
                'name'                   => ['required', 'string', 'max:255'],
                'phone_number'           => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
                'email'                  => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password'               => ['required', Rules\Password::defaults()],
                'nationality'            => ['required', 'string', 'in:' . implode(',', config('nationalities', []))],
                'short_address'          => ['required', 'string', 'max:500'],
                'location_lat'           => ['required', 'numeric', 'between:-90,90'],
                'location_lng'           => ['required', 'numeric', 'between:-180,180'],
                'id_type'                => ['required', 'string', 'in:' . implode(',', RecipientProfile::ID_TYPES)],
                'id_number'              => ['required', 'digits:10'],
                'id_photo_base64'        => ['required', 'string', new Base64Image],
                'income_band'            => ['required', 'string', 'in:' . implode(',', RecipientKycDetails::INCOME_BANDS)],
                'household_size'         => ['required', 'integer', 'min:1', 'max:50'],
                'marital_status'         => ['required', 'string', 'in:' . implode(',', RecipientKycDetails::MARITAL_STATUSES)],
                'is_student'             => ['required', 'boolean'],
                'employment_status'      => ['required', 'string', 'in:' . implode(',', RecipientKycDetails::EMPLOYMENT_STATUSES)],
                'situation_description'  => ['required', 'string', 'min:10', 'max:1000'],
            ]);
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('membership_type') !== User::MEMBERSHIP_RECIPIENT) {
                return;
            }
            if ($this->hasFile('id_photo')) {
                abort(422, 'Use camera capture only. File upload is not allowed for identity proof.');
            }
        });
    }
}
