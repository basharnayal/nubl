<?php

namespace App\Auth\Registration\Data;

use App\Http\Requests\Auth\StoreRegisteredUserRequest;

class RecipientRegistrationData extends RegistrationData
{
    public function __construct(
        string $name,
        string $email,
        string $password,
        string $phoneNumber,
        public readonly string $nationality,
        public readonly string $shortAddress,
        public readonly float $locationLat,
        public readonly float $locationLng,
        public readonly string $idType,
        public readonly string $idNumber,
        public readonly string $idPhotoBase64,
        public readonly string $incomeBand,
        public readonly int $householdSize,
        public readonly string $maritalStatus,
        public readonly bool $isStudent,
        public readonly string $employmentStatus,
        public readonly ?string $situationDescription,
    ) {
        parent::__construct($name, $email, $password, $phoneNumber);
    }

    public static function fromRequest(StoreRegisteredUserRequest $request): self
    {
        $v = $request->validated();

        return new self(
            name: $v['name'],
            email: $v['email'],
            password: $v['password'],
            phoneNumber: $v['phone_number'],
            nationality: $v['nationality'],
            shortAddress: $v['short_address'],
            locationLat: (float) $v['location_lat'],
            locationLng: (float) $v['location_lng'],
            idType: $v['id_type'],
            idNumber: $v['id_number'],
            idPhotoBase64: $v['id_photo_base64'],
            incomeBand: $v['income_band'],
            householdSize: (int) $v['household_size'],
            maritalStatus: $v['marital_status'],
            isStudent: (bool) $v['is_student'],
            employmentStatus: $v['employment_status'],
            situationDescription: $v['situation_description'] ?? null,
        );
    }
}
