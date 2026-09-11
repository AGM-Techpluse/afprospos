<?php

declare(strict_types=1);

namespace Tests\Integration\Inventory;

use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Handlers\ConsumeInventoryHandler;
use Domain\Inventory\Application\Handlers\ReleaseInventoryHandler;
use Domain\Inventory\Application\Handlers\ReserveInventoryHandler;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Command -> Handler -> real repository -> real DB, no HTTP (CPNC §6's
 * Integration tier) — proves the reservation engine's correctness.
 * The genuinely-parallel race itself is proven separately by the
 * Concurrency suite.
 */
class ReserveInventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserves_non_serialized_quantity_when_available(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $result = app(ReserveInventoryHandler::class)->handle(new ReserveInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 4,
            sourceType: 'checkout',
            sourceId: 1,
        ));

        $this->assertSame([], $result->inventoryItemIds);
        $this->assertDatabaseHas('inventory_stock_levels', [
            'sku_id' => $sku->id,
            'shop_id' => $shop->id,
            'on_hand' => 10,
            'reserved' => 4,
        ]);
    }

    public function test_refuses_to_reserve_more_than_is_available(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 3, 'reserved' => 0]);

        try {
            app(ReserveInventoryHandler::class)->handle(new ReserveInventoryCommand(
                skuId: $sku->id,
                shopId: $shop->id,
                quantity: 4,
                sourceType: 'checkout',
                sourceId: 1,
            ));
            $this->fail('Expected InsufficientAvailableStock to be thrown.');
        } catch (InsufficientAvailableStock) {
            // expected
        }

        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 0]);
    }

    public function test_reserves_a_specific_serialized_unit(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        $item = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        $result = app(ReserveInventoryHandler::class)->handle(new ReserveInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 1,
            sourceType: 'checkout',
            sourceId: 7,
        ));

        $this->assertSame([$item->id], $result->inventoryItemIds);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => 'reserved',
            'reserved_by_type' => 'checkout',
            'reserved_by_id' => 7,
        ]);
    }

    public function test_an_already_reserved_imei_cannot_be_reserved_twice(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        InventoryItemRecord::factory()->reserved('checkout', 1)->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        $this->expectException(InsufficientAvailableStock::class);

        app(ReserveInventoryHandler::class)->handle(new ReserveInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 1,
            sourceType: 'checkout',
            sourceId: 2,
        ));
    }

    public function test_release_returns_non_serialized_quantity_to_available(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 4]);

        app(ReleaseInventoryHandler::class)->handle(new ReleaseInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 4,
            sourceType: 'checkout',
            sourceId: 1,
        ));

        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);
    }

    public function test_release_returns_a_reserved_serialized_unit_to_available(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        $item = InventoryItemRecord::factory()->reserved('checkout', 9)->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        app(ReleaseInventoryHandler::class)->handle(new ReleaseInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 1,
            sourceType: 'checkout',
            sourceId: 9,
        ));

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'available', 'reserved_by_id' => null]);
    }

    public function test_consume_converts_a_non_serialized_reservation_into_a_stock_out(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 4]);

        app(ConsumeInventoryHandler::class)->handle(new ConsumeInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 4,
            sourceType: 'checkout',
            sourceId: 1,
        ));

        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 6, 'reserved' => 0]);
    }

    public function test_consume_marks_a_reserved_serialized_unit_sold(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        $item = InventoryItemRecord::factory()->reserved('checkout', 3)->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        app(ConsumeInventoryHandler::class)->handle(new ConsumeInventoryCommand(
            skuId: $sku->id,
            shopId: $shop->id,
            quantity: 1,
            sourceType: 'checkout',
            sourceId: 3,
        ));

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'sold']);
    }
}
