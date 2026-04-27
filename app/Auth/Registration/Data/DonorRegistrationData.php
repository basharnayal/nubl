<?php

namespace App\Auth\Registration\Data;

use App\Http\Requests\Auth\StoreRegisteredUserRequest;

class DonorRegistrationData extends RegistrationData
{
    public static function fromRequest(StoreRegisteredUserRequest $request): self
    {
        $v = $request->validated();

        return new self(
            name:        $v['name'],
            email:       $v['email'],
            password:    $v['password'],
            phoneNumber: $v['phone_number'],
        );
    }
}
