<?php

namespace Tests\Unit\Helpers;

use App\Support\PhoneHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoneHelperTest extends TestCase
{
    #[Test]
    #[DataProvider('normalizeProvider')]
    public function it_normalizes_saudi_numbers_to_e164_digits(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneHelper::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalizeProvider(): array
    {
        return [
            '05 prefix' => ['050 123 4567', '966501234567'],
            'without leading zero' => ['501234567', '966501234567'],
            'with +966' => ['+966 50 123 4567', '966501234567'],
            'with 966' => ['966501234567', '966501234567'],
            'with 00966' => ['00966501234567', '966501234567'],
        ];
    }

    #[Test]
    public function it_validates_mobile_prefixes_1_2_5(): void
    {
        $this->assertTrue(PhoneHelper::isValid('0501234567'));
        $this->assertTrue(PhoneHelper::isValid('+966 55 000 0000'));
        $this->assertFalse(PhoneHelper::isValid('0300000000'));
        $this->assertFalse(PhoneHelper::isValid('123'));
    }

    #[Test]
    public function format_for_input_adds_leading_zero(): void
    {
        $this->assertSame('0501234567', PhoneHelper::formatForInput('966501234567'));
    }

    #[Test]
    public function format_for_display_groups_digits(): void
    {
        $this->assertSame('+966 50 123 4567', PhoneHelper::formatForDisplay('0501234567'));
    }

    #[Test]
    public function mask_for_log_hides_middle_digits(): void
    {
        $this->assertSame('966*******67', PhoneHelper::maskForLog('966501234567'));
        $this->assertSame('966*******67', PhoneHelper::maskForLog('+966 50 123 4567'));
        $this->assertSame('****', PhoneHelper::maskForLog('1234'));
        $this->assertSame('', PhoneHelper::maskForLog(''));
        $this->assertSame('', PhoneHelper::maskForLog(null));
    }
}
