<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use Domain\Inventory\Domain\Entities\InventoryStockLevel;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Domain\ValueObjects\StockLevelId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InventoryStockLevelTest extends TestCase
{
    private function level(int $onHand, int $reserved): InventoryStockLevel
    {
        return InventoryStockLevel::reconstitute(new StockLevelId(1), new SkuId(1), new ShopId(1), $onHand, $reserved, 0);
    }

    public function test_reserve_increments_reserved_when_available(): void
    {
        $level = $this->level(10, 2);
        $level->reserve(3);

        $this->assertSame(5, $level->reserved());
        $this->assertSame(5, $level->availableQuantity());
        $this->assertSame(1, $level->version());
    }

    public function test_reserve_refuses_more_than_available(): void
    {
        $level = $this->level(5, 5);

        $this->expectException(InsufficientAvailableStock::class);
        $level->reserve(1);
    }

    public function test_release_decrements_reserved(): void
    {
        $level = $this->level(10, 4);
        $level->release(4);

        $this->assertSame(0, $level->reserved());
    }

    public function test_release_refuses_more_than_currently_reserved(): void
    {
        $level = $this->level(10, 2);

        $this->expectException(InvalidArgumentException::class);
        $level->release(3);
    }

    public function test_consume_reduces_both_on_hand_and_reserved(): void
    {
        $level = $this->level(10, 4);
        $level->consume(4);

        $this->assertSame(6, $level->onHand());
        $this->assertSame(0, $level->reserved());
    }

    public function test_receive_increases_on_hand_only(): void
    {
        $level = $this->level(10, 3);
        $level->receive(5);

        $this->assertSame(15, $level->onHand());
        $this->assertSame(3, $level->reserved());
    }

    public function test_adjust_can_reduce_on_hand(): void
    {
        $level = $this->level(10, 0);
        $level->adjust(-3);

        $this->assertSame(7, $level->onHand());
    }

    public function test_adjust_refuses_to_go_below_reserved(): void
    {
        $level = $this->level(10, 8);

        $this->expectException(InvalidArgumentException::class);
        $level->adjust(-5);
    }

    public function test_transfer_out_requires_available_quantity(): void
    {
        $level = $this->level(10, 8);

        $this->expectException(InsufficientAvailableStock::class);
        $level->transferOut(5);
    }

    public function test_transfer_out_reduces_on_hand_without_touching_reserved(): void
    {
        $level = $this->level(10, 2);
        $level->transferOut(5);

        $this->assertSame(5, $level->onHand());
        $this->assertSame(2, $level->reserved());
    }
}
