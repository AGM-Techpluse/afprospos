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

class CashierCanCreateCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_create_a_checkout_and_add_an_item(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 25000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', [
            'shop_id' => $shop->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_checkouts', ['shop_id' => $shop->id, 'cashier_staff_id' => $cashier->id, 'status' => 'open']);

        $checkoutId = SalesCheckoutRecord::query()->latest('id')->first()->id;

        $itemResponse = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkoutId}/items", [
            'sku_id' => $sku->id,
            'quantity' => 2,
        ]);

        $itemResponse->assertRedirect();
        $this->assertDatabaseHas('sales_checkout_items', ['sales_checkout_id' => $checkoutId, 'sku_id' => $sku->id, 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 2]);
    }

    public function test_a_staff_member_without_sales_create_cannot_start_a_checkout(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician'); // no sales.create per config/afprospos.php
        $shop = ShopRecord::factory()->create();

        $response = $this->actingAs($technician, 'staff')->post('/admin/sales/checkout', [
            'shop_id' => $shop->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sales_checkouts', ['shop_id' => $shop->id]);
    }
}
