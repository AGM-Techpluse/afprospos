<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use Domain\Shared\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_adds_two_money_values_in_minor_units(): void
    {
        $total = (new Money(1_000_00))->add(new Money(50_00));
        $this->assertEquals(1_050_00, $total->minor);
    }

    public function test_refuses_to_subtract_more_than_is_present(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Money(100))->subtract(new Money(200));
    }

    public function test_refuses_a_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-1);
    }

    public function test_reports_zero_correctly(): void
    {
        $this->assertTrue(Money::zero()->isZero());
        $this->assertFalse((new Money(1))->isZero());
    }
}
