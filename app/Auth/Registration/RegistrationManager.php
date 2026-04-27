<?php

namespace App\Auth\Registration;

use App\Auth\Registration\Data\DonorRegistrationData;
use App\Auth\Registration\Data\ProviderRegistrationData;
use App\Auth\Registration\Data\RecipientRegistrationData;
use App\Auth\Registration\Data\RegistrationData;
use App\Auth\Registration\Registrars\DonorRegistrar;
use App\Auth\Registration\Registrars\ProviderRegistrar;
use App\Auth\Registration\Registrars\RecipientRegistrar;
use App\Models\User;

/**
 * Routes a RegistrationData DTO to the correct Registrar.
 * Adding a new account type = new DTO class + new Registrar + one match arm here.
 */
class RegistrationManager
{
    public function __construct(
        private DonorRegistrar $donor,
        private RecipientRegistrar $recipient,
        private ProviderRegistrar $provider,
    ) {}

    public function register(RegistrationData $data): User
    {
        return match (true) {
            $data instanceof DonorRegistrationData     => $this->donor->register($data),
            $data instanceof RecipientRegistrationData => $this->recipient->register($data),
            $data instanceof ProviderRegistrationData  => $this->provider->register($data),
            default => throw new \InvalidArgumentException(
                'Unsupported registration data type: ' . $data::class
            ),
        };
    }
}
