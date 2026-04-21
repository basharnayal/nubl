<?php

namespace Tests\Unit\Rules;

use App\Models\User;
use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaudiPhoneRulesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('validPhoneNumbers')]
    public function saudi_phone_number_rule_accepts_valid_formats(string $input): void
    {
        $validator = Validator::make(
            ['phone_number' => $input],
            ['phone_number' => [new SaudiPhoneNumber()]]
        );

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validPhoneNumbers(): array
    {
        return [
            'with leading zero' => ['0501234567'],
            'without leading zero' => ['501234567'],
            'with +966' => ['+966501234567'],
            'with 966' => ['966501234567'],
            'with 00966' => ['00966501234567'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPhoneNumbers')]
    public function saudi_phone_number_rule_rejects_invalid_formats(mixed $input): void
    {
        $validator = Validator::make(
            ['phone_number' => $input],
            ['phone_number' => [new SaudiPhoneNumber()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone_number', $validator->errors()->toArray());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidPhoneNumbers(): array
    {
        return [
            'wrong prefix' => ['0301234567'],
            'too short' => ['50123'],
            'non-string' => [123456],
        ];
    }

    #[Test]
    public function saudi_phone_unique_rejects_existing_number_after_normalization(): void
    {
        User::factory()->create(['phone_number' => '966501234567']);

        $validator = Validator::make(
            ['phone_number' => '0501234567'],
            ['phone_number' => [new SaudiPhoneUnique()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone_number', $validator->errors()->toArray());
    }

    #[Test]
    public function saudi_phone_unique_allows_same_number_when_current_user_is_ignored(): void
    {
        $user = User::factory()->create(['phone_number' => '966501234567']);

        $validator = Validator::make(
            ['phone_number' => '0501234567'],
            ['phone_number' => [new SaudiPhoneUnique($user->id)]]
        );

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    #[Test]
    public function saudi_phone_unique_rejects_non_string_values(): void
    {
        $validator = Validator::make(
            ['phone_number' => 501234567],
            ['phone_number' => [new SaudiPhoneUnique()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone_number', $validator->errors()->toArray());
    }
}
