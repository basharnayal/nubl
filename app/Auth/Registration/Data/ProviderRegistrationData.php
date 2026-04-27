<?php

namespace App\Auth\Registration\Data;

use App\Http\Requests\Auth\StoreProviderRegistrationRequest;
use Illuminate\Http\UploadedFile;

class ProviderRegistrationData extends RegistrationData
{
    /**
     * @param  string[]  $businessCategory
     * @param  string[]  $serviceType
     * @param  array<string, array{open?: string, close?: string, closed: bool}>  $operatingHours
     */
    public function __construct(
        string $name,
        string $email,
        string $password,
        string $phoneNumber,
        public readonly string       $fullNameAr,
        public readonly string       $fullNameEn,
        public readonly string       $businessNameAr,
        public readonly string       $businessNameEn,
        public readonly string       $unifiedNumber,
        public readonly array        $businessCategory,
        public readonly string       $addressAr,
        public readonly string       $addressEn,
        public readonly string       $city,
        public readonly string       $region,
        public readonly ?string      $location,
        public readonly int          $dailyCapacity,
        public readonly array        $serviceType,
        public readonly string       $estimatedPreparationOrderTime,
        public readonly string       $adoptionSupport,
        public readonly string       $bankName,
        public readonly string       $iban,
        public readonly string       $accountHolderName,
        public readonly array        $operatingHours,
        public readonly UploadedFile $businessLicense,
        public readonly UploadedFile $idOrIqama,
        public readonly ?UploadedFile $profileLogo,
    ) {
        parent::__construct($name, $email, $password, $phoneNumber);
    }

    public static function fromRequest(StoreProviderRegistrationRequest $request): self
    {
        $v = $request->validated();

        return new self(
            name:                           $v['full_name_en'],
            email:                          $v['email'],
            password:                       $v['password'],
            phoneNumber:                    $v['phone_number'],
            fullNameAr:                     $v['full_name_ar'],
            fullNameEn:                     $v['full_name_en'],
            businessNameAr:                 $v['business_name_ar'],
            businessNameEn:                 $v['business_name_en'],
            unifiedNumber:                  $v['unified_number'],
            businessCategory:               $v['business_category'],
            addressAr:                      $v['address_ar'],
            addressEn:                      $v['address_en'],
            city:                           $v['city'],
            region:                         $v['region'],
            location:                       $v['location'] ?? null,
            dailyCapacity:                  (int) $v['daily_capacity'],
            serviceType:                    $v['service_type'],
            estimatedPreparationOrderTime:  $v['estimated_preparation_order_time'],
            adoptionSupport:                $v['adoption_support'],
            bankName:                       $v['bank_name'],
            iban:                           $v['iban'],
            accountHolderName:              $v['account_holder_name'],
            operatingHours:                 $request->normalizedOperatingHours(),
            businessLicense:                $request->file('business_license'),
            idOrIqama:                      $request->file('id_or_iqama'),
            profileLogo:                    $request->hasFile('profile_logo') ? $request->file('profile_logo') : null,
        );
    }
}
