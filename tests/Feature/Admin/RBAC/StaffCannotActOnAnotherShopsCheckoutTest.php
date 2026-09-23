<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BRD RBAC-05: holding `sales.create` proves a cashier can build checkouts in general — never that this specific checkout's shop was granted to them. */
class StaffCannotActOnAnotherShopsCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_granted_only_shop_a_cannot_add_an_item_to_shop_bs_checkout(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shopA->id);

        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shopB->id]);
        $sku = SkuRecord::factory()->nonSerialized()->create();

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", [
            'sku_id' => $sku->id,
            'quantity' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_a_cashier_cannot_create_a_checkout_for_a_shop_they_were_not_granted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no grant to $shop at all

        $response = $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sales_checkouts', ['shop_id' => $shop->id]);
    }
}
