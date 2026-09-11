<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use Domain\Inventory\Domain\Entities\InventoryItem;
use Domain\Inventory\Domain\Exceptions\ItemNotReservable;
use Domain\Inventory\Domain\Exceptions\ItemNotTransferable;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use PHPUnit\Framework\TestCase;

class InventoryItemTest extends TestCase
{
    private function item(string $status = 'available', ?string $reservedByType = null, ?int $reservedById = null): InventoryItem
    {
        return InventoryItem::reconstitute(
            new InventoryItemId(1),
            new SkuId(1),
            '123456789012345',
            new ShopId(1),
            'new',
            $status,
            $reservedByType,
            $reservedById,
            0,
        );
    }

    public function test_reserve_succeeds_when_available_at_the_requested_shop(): void
    {
        $item = $this->item();
        $item->reserve(new ShopId(1), 'checkout', 42);

        $this->assertSame('reserved', $item->status());
        $this->assertSame('checkout', $item->reservedByType());
        $this->assertSame(42, $item->reservedById());
        $this->assertSame(1, $item->version());
    }

    public function test_an_already_reserved_item_cannot_be_reserved_again(): void
    {
        $item = $this->item('reserved', 'checkout', 1);

        $this->expectException(ItemNotReservable::class);
        $item->reserve(new ShopId(1), 'checkout', 2);
    }

    public function test_reserve_refuses_a_request_for_the_wrong_shop(): void
    {
        $item = $this->item();

        $this->expectException(ItemNotReservable::class);
        $item->reserve(new ShopId(2), 'checkout', 1);
    }

    public function test_release_returns_the_item_to_available(): void
    {
        $item = $this->item('reserved', 'checkout', 1);
        $item->release();

        $this->assertSame('available', $item->status());
        $this->assertNull($item->reservedByType());
        $this->assertNull($item->reservedById());
    }

    public function test_consume_requires_a_prior_reservation(): void
    {
        $item = $this->item();

        $this->expectException(ItemNotReservable::class);
        $item->consume();
    }

    public function test_consume_marks_the_item_sold(): void
    {
        $item = $this->item('reserved', 'checkout', 1);
        $item->consume();

        $this->assertSame('sold', $item->status());
    }

    public function test_a_reserved_item_cannot_begin_a_transfer(): void
    {
        $item = $this->item('reserved', 'checkout', 1);

        $this->expectException(ItemNotTransferable::class);
        $item->beginTransfer();
    }

    public function test_transfer_lifecycle_moves_the_item_to_the_destination_shop(): void
    {
        $item = $this->item();
        $item->beginTransfer();
        $this->assertSame('transferring', $item->status());

        $item->completeTransfer(new ShopId(2));

        $this->assertSame('available', $item->status());
        $this->assertTrue($item->currentShopId()->equals(new ShopId(2)));
    }

    public function test_cancelling_a_transfer_returns_the_item_to_available_at_its_original_shop(): void
    {
        $item = $this->item();
        $item->beginTransfer();
        $item->cancelTransfer();

        $this->assertSame('available', $item->status());
        $this->assertTrue($item->currentShopId()->equals(new ShopId(1)));
    }
}
