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

class AdminCanCompleteCashSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_complete_a_cash_sale_and_receives_an_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 30000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 1]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/complete", [
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_sales', ['sales_checkout_id' => $checkout->id, 'total_minor' => 30000, 'payment_method' => 'cash']);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'paid']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 4, 'reserved' => 0]);
    }

    public function test_completing_an_already_expired_checkout_fails_and_does_not_mutate_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $checkout = SalesCheckoutRecord::factory()->expired()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashier->id,
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/complete", [
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkout->id]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'open']);
    }
}
