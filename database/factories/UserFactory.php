<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'membership_type' => \App\Models\User::MEMBERSHIP_DONOR,
            'status' => \App\Models\User::STATUS_ACTIVE,
            'phone_number' => '0501234567',
        ];
    }

    /**
     * Indicate that the user is a recipient with pending approval.
     */
    public function recipientPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_type' => \App\Models\User::MEMBERSHIP_RECIPIENT,
            'status' => \App\Models\User::STATUS_PENDING_APPROVAL,
            'phone_number' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
