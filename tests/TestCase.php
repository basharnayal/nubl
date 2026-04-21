<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with the admin role.
     */
    protected function admin(array $attributes = []): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::factory()->create(array_merge([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ], $attributes));

        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create a user with the donor role.
     */
    protected function donor(array $attributes = []): User
    {
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $user = User::factory()->create(array_merge([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ], $attributes));

        $user->assignRole('donor');

        return $user;
    }

    /**
     * Create a user with the recipient role.
     */
    protected function recipient(array $attributes = []): User
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $user = User::factory()->create(array_merge([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ], $attributes));

        $user->assignRole('recipient');

        return $user;
    }

    /**
     * Create a user with the provider role.
     */
    protected function provider(array $attributes = []): User
    {
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $user = User::factory()->create(array_merge([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ], $attributes));

        $user->assignRole('provider');

        return $user;
    }

    /**
     * Create an admin and authenticate as that user.
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = $this->admin($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a donor and authenticate as that user.
     */
    protected function actingAsDonor(array $attributes = []): User
    {
        $user = $this->donor($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a recipient and authenticate as that user.
     */
    protected function actingAsRecipient(array $attributes = []): User
    {
        $user = $this->recipient($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a provider and authenticate as that user.
     */
    protected function actingAsProvider(array $attributes = []): User
    {
        $user = $this->provider($attributes);
        $this->actingAs($user);

        return $user;
    }
}
