<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Application\Commands\UpdateCheckoutItemQuantityCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\RemoveCheckoutItemHandler;
use Domain\Sales\Application\Handlers\UpdateCheckoutItemQuantityHandler;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
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

    public function test_create_checkout_returns_the_cashiers_existing_open_checkout_instead_of_a_duplicate(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $firstId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        $secondId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));

        $this->assertSame($firstId->value, $secondId->value);
        $this->assertSame(1, SalesCheckoutRecord::query()->where('cashier_staff_id', $staff->id)->count());
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

    public function test_increasing_a_non_serialized_items_quantity_reserves_the_delta(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 10000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));
        $itemId = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkoutId->value)->firstOrFail()->id;

        app(UpdateCheckoutItemQuantityHandler::class)->handle(new UpdateCheckoutItemQuantityCommand($checkoutId->value, $itemId, 4));

        $this->assertDatabaseHas('sales_checkout_items', ['id' => $itemId, 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 4]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'subtotal_minor' => 40000, 'total_minor' => 40000]);
    }

    public function test_decreasing_a_non_serialized_items_quantity_releases_the_delta(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 10000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 5));
        $itemId = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkoutId->value)->firstOrFail()->id;

        app(UpdateCheckoutItemQuantityHandler::class)->handle(new UpdateCheckoutItemQuantityCommand($checkoutId->value, $itemId, 2));

        $this->assertDatabaseHas('sales_checkout_items', ['id' => $itemId, 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 2]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'subtotal_minor' => 20000, 'total_minor' => 20000]);
    }

    public function test_a_serialized_items_quantity_cannot_be_changed(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        InventoryItemRecord::factory()->create([
            'sku_id' => $sku->id,
            'current_shop_id' => $shop->id,
            'status' => 'available',
        ]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));
        $itemId = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkoutId->value)->firstOrFail()->id;

        $this->expectException(SerializedItemQuantityMustBeOne::class);

        app(UpdateCheckoutItemQuantityHandler::class)->handle(new UpdateCheckoutItemQuantityCommand($checkoutId->value, $itemId, 2));
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
