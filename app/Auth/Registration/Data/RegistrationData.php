<?php

namespace App\Auth\Registration\Data;

/**
 * Base value object carrying fields common to all account types.
 * Subclasses add type-specific data.
 */
abstract class RegistrationData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $phoneNumber,
    ) {}
}
