<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanChangeCheckoutItemQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_increase_a_non_serialized_lines_quantity(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 10000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 1]);
        $item = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkout->id)->firstOrFail();

        $response = $this->actingAs($cashier, 'staff')->patch("/admin/sales/checkout/{$checkout->id}/items/{$item->id}", ['quantity' => 3]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id, 'quantity' => 3]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 3]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'total_minor' => 30000]);
    }

    public function test_a_serialized_lines_quantity_cannot_be_changed_over_http(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id, 'status' => 'available']);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 1]);
        $item = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkout->id)->firstOrFail();

        $response = $this->actingAs($cashier, 'staff')->patch("/admin/sales/checkout/{$checkout->id}/items/{$item->id}", ['quantity' => 2]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_a_staff_member_without_sales_create_cannot_change_quantity(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff'); // sales => ['view'] only, per config/afprospos.php
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
        $item = SalesCheckoutItemRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'quantity' => 1]);

        $response = $this->actingAs($productStaff, 'staff')->patch("/admin/sales/checkout/{$checkout->id}/items/{$item->id}", ['quantity' => 2]);

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id, 'quantity' => 1]);
    }
}
