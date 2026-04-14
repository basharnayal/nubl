<?php

namespace Tests\Unit\Support;

use App\Support\FinancialMath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FinancialMathTest extends TestCase
{
    #[Test]
    public function decimal_addition_uses_bcmath_scale_without_float_drift(): void
    {
        $this->assertSame('0.30', FinancialMath::add('0.10', '0.20'));
        $this->assertSame('1000000.99', FinancialMath::add('999999.99', '1.00'));
    }

    #[Test]
    public function normalize_subtract_and_compare_use_two_decimal_scale(): void
    {
        $this->assertSame('10.12', FinancialMath::normalize(' 10.129 '));
        $this->assertSame('84.87', FinancialMath::sub('100.00', '15.13'));
        $this->assertSame(0, FinancialMath::compare('10.129', '10.12'));
        $this->assertSame(1, FinancialMath::compare('10.13', '10.12'));
        $this->assertSame(-1, FinancialMath::compare('10.11', '10.12'));
    }
}
