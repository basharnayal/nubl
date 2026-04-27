<?php

namespace App\Auth\Registration\Registrars;

use App\Models\User;

class DonorRegistrar extends AbstractAccountRegistrar
{
    protected function membershipType(): string { return User::MEMBERSHIP_DONOR; }

    protected function initialStatus(): string { return User::STATUS_ACTIVE; }

    protected function roleName(): string { return 'donor'; }

    // No profiles, no files — all hooks use the inherited no-op defaults.
}
