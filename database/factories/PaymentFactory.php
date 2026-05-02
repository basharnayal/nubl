<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'sponsor_id' => User::factory(),
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => (string) fake()->unique()->randomNumber(6),
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => fake()->randomFloat(2, 10, 500),
            'notes' => null,
            'idempotency_key' => fake()->uuid(),
            'is_guest' => false,
            'is_anonymous' => false,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_SUCCEEDED,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'sponsor_id' => null,
            'is_guest' => true,
            'is_anonymous' => true,
        ]);
    }
}
