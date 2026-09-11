<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanCancelCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_cancel_an_open_checkout_and_release_its_reservations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 2]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/cancel");

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 0]);
    }

    public function test_a_staff_member_without_sales_cancel_cannot_cancel_a_checkout(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff'); // sales => ['view'] only, per config/afprospos.php
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($productStaff, 'staff')->post("/admin/sales/checkout/{$checkout->id}/cancel");

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'open']);
    }
}
