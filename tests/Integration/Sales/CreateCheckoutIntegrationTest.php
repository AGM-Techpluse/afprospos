<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\RemoveCheckoutItemHandler;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> real repositories -> real DB, no HTTP (CPNC §6's Integration tier). */
class CreateCheckoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_checkout_creates_an_open_shell_with_no_items(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $id = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand(
            shopId: $shop->id,
            customerId: null,
            cashierStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('sales_checkouts', [
            'id' => $id->value,
            'shop_id' => $shop->id,
            'status' => 'open',
            'total_minor' => 0,
        ]);
    }

    public function test_add_item_reserves_inventory_and_snapshots_price(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 15000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));

        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 2));

        $this->assertDatabaseHas('sales_checkout_items', [
            'sales_checkout_id' => $checkoutId->value,
            'sku_id' => $sku->id,
            'quantity' => 2,
            'unit_price_minor' => 15000,
        ]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 2]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'subtotal_minor' => 30000, 'total_minor' => 30000]);
    }

    public function test_remove_item_releases_the_reservation_and_recomputes_totals(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 10000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $itemId = SalesCheckoutItemRecord::query()
            ->where('sales_checkout_id', $checkoutId->value)->firstOrFail()->id;

        app(RemoveCheckoutItemHandler::class)->handle(new RemoveCheckoutItemCommand($checkoutId->value, $itemId));

        $this->assertDatabaseMissing('sales_checkout_items', ['id' => $itemId]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 0]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'subtotal_minor' => 0, 'total_minor' => 0]);
    }

    public function test_add_item_refuses_once_the_checkout_is_no_longer_open(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $staff->id,
            'status' => 'cancelled',
        ]);

        $this->expectException(CheckoutNotOpen::class);

        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkout->id, $sku->id, 1));
    }
}
