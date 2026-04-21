<?php

namespace Tests\Unit\Rules;

use App\Rules\Base64Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Base64ImageRuleTest extends TestCase
{
    private const VALID_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    #[Test]
    public function base64_image_rule_accepts_valid_base64_data_url(): void
    {
        $validator = Validator::make(
            ['photo' => self::VALID_BASE64_IMAGE],
            ['photo' => [new Base64Image()]]
        );

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    #[Test]
    public function base64_image_rule_rejects_non_string_values(): void
    {
        $validator = Validator::make(
            ['photo' => UploadedFile::fake()->image('photo.jpg')],
            ['photo' => [new Base64Image()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('photo', $validator->errors()->toArray());
    }

    #[Test]
    public function base64_image_rule_rejects_invalid_data_url_prefix(): void
    {
        $validator = Validator::make(
            ['photo' => 'data:text/plain;base64,SGVsbG8='],
            ['photo' => [new Base64Image()]]
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function base64_image_rule_rejects_empty_payload(): void
    {
        $validator = Validator::make(
            ['photo' => 'data:image/png;base64,'],
            ['photo' => [new Base64Image()]]
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function base64_image_rule_rejects_invalid_base64_payload(): void
    {
        $validator = Validator::make(
            ['photo' => 'data:image/png;base64,%%%not_base64%%%'],
            ['photo' => [new Base64Image()]]
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function base64_image_rule_rejects_images_larger_than_five_megabytes(): void
    {
        $raw = str_repeat('a', (5 * 1024 * 1024) + 1);
        $oversized = 'data:image/png;base64,'.base64_encode($raw);

        $validator = Validator::make(
            ['photo' => $oversized],
            ['photo' => [new Base64Image()]]
        );

        $this->assertTrue($validator->fails());
    }
}
