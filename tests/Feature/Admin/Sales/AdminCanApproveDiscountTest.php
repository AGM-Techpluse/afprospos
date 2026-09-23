<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutAdjustmentRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** MKT-BR-15: every checkout discount requires Shop Owner approval before the checkout can be paid. */
class AdminCanApproveDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shop_owner_can_approve_a_pending_discount(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
        SalesCheckoutAdjustmentRecord::factory()->create(['sales_checkout_id' => $checkout->id]);

        $response = $this->actingAs($owner, 'staff')->post("/admin/sales/checkout/{$checkout->id}/discount/approve");

        $response->assertRedirect();
        // Saving a checkout deletes and recreates its adjustment rows (EloquentSalesCheckoutRepository::syncAdjustments),
        // so the approved row's id differs from $adjustment->id — assert by checkout instead of by the now-stale id.
        $this->assertDatabaseHas('sales_checkout_adjustments', [
            'sales_checkout_id' => $checkout->id,
            'approval_status' => 'approved',
            'approved_by_staff_id' => $owner->id,
        ]);
        $this->assertDatabaseCount('sales_checkout_adjustments', 1);
    }

    public function test_a_cashier_cannot_approve_a_discount(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
        SalesCheckoutAdjustmentRecord::factory()->create(['sales_checkout_id' => $checkout->id]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/discount/approve");

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_checkout_adjustments', ['sales_checkout_id' => $checkout->id, 'approval_status' => 'pending']);
    }

    public function test_a_cashier_cannot_complete_a_checkout_with_an_unapproved_discount(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashier->id,
            'subtotal_minor' => 10000,
            'total_minor' => 9000,
        ]);
        SalesCheckoutItemRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'unit_price_minor' => 10000, 'quantity' => 1]);
        SalesCheckoutAdjustmentRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'amount_minor' => 1000]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/complete", [
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkout->id]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'open']);
    }

    public function test_a_cashier_can_complete_a_checkout_once_its_discount_is_approved(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 30000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 1]);
        SalesCheckoutAdjustmentRecord::factory()->approved()->create(['sales_checkout_id' => $checkout->id, 'amount_minor' => 1000]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/complete", [
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_sales', ['sales_checkout_id' => $checkout->id]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'paid']);
    }
}
