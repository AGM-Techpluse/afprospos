<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use Domain\Inventory\Domain\Services\StockAvailability;
use PHPUnit\Framework\TestCase;

class StockAvailabilityTest extends TestCase
{
    public function test_available_is_on_hand_minus_reserved(): void
    {
        $this->assertSame(7, StockAvailability::available(10, 3));
    }

    public function test_available_can_be_zero(): void
    {
        $this->assertSame(0, StockAvailability::available(5, 5));
    }
}
