<?php

namespace App\Auth\Registration\Contracts;

use App\Auth\Registration\Data\RegistrationData;
use App\Models\User;

interface AccountRegistrar
{
    public function register(RegistrationData $data): User;
}
