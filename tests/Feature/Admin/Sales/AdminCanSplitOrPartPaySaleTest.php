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

class AdminCanSplitOrPartPaySaleTest extends TestCase
{
    use RefreshDatabase;

    private function openCheckoutWithItem(StaffRecord $cashier, ShopRecord $shop, int $priceMinor): int
    {
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => $priceMinor]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);
        $checkout = SalesCheckoutRecord::query()->latest('id')->first();
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/items", ['sku_id' => $sku->id, 'quantity' => 1]);

        return $checkout->id;
    }

    public function test_a_cashier_can_split_a_sale_across_two_methods_reaching_the_full_total(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $checkoutId = $this->openCheckoutWithItem($cashier, $shop, 30000);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkoutId}/collect-payment", [
            'payments' => [
                ['method' => 'cash', 'amount_minor' => 18000, 'reference' => null],
                ['method' => 'pos_terminal', 'amount_minor' => 12000, 'reference' => 'TERM-REF-1'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_sales', ['sales_checkout_id' => $checkoutId, 'total_minor' => 30000, 'payment_method' => 'split']);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId, 'status' => 'paid']);
        $this->assertDatabaseCount('payments_transactions', 2);
    }

    public function test_a_partial_payment_leaves_the_checkout_open_and_a_second_payment_completes_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $checkoutId = $this->openCheckoutWithItem($cashier, $shop, 30000);

        $firstResponse = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkoutId}/collect-payment", [
            'payments' => [['method' => 'cash', 'amount_minor' => 10000, 'reference' => null]],
        ]);

        $firstResponse->assertRedirect(route('admin.sales.checkout', ['checkout' => $checkoutId]));
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkoutId]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId, 'status' => 'open']);
        $this->assertDatabaseHas('payments_transactions', ['payable_id' => $checkoutId, 'status' => 'confirmed', 'amount_minor' => 10000]);

        $secondResponse = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkoutId}/collect-payment", [
            'payments' => [['method' => 'cash', 'amount_minor' => 20000, 'reference' => null]],
        ]);

        $secondResponse->assertRedirect();
        $this->assertDatabaseHas('sales_sales', ['sales_checkout_id' => $checkoutId, 'total_minor' => 30000]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId, 'status' => 'paid']);
    }

    public function test_bank_transfer_cannot_be_combined_with_another_method_in_one_split_submission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $checkoutId = $this->openCheckoutWithItem($cashier, $shop, 30000);

        $response = $this->actingAs($cashier, 'staff')
            ->from('/admin/sales/checkout')
            ->post("/admin/sales/checkout/{$checkoutId}/collect-payment", [
                'payments' => [
                    ['method' => 'cash', 'amount_minor' => 15000, 'reference' => null],
                    ['method' => 'bank_transfer', 'amount_minor' => 15000, 'reference' => 'BANK-REF-1'],
                ],
            ]);

        $response->assertSessionHasErrors('payments');
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkoutId]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId, 'status' => 'open']);
    }

    public function test_a_non_cash_leg_without_a_reference_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($cashier, $shop->id);
        $checkoutId = $this->openCheckoutWithItem($cashier, $shop, 30000);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkoutId}/collect-payment", [
            'payments' => [['method' => 'pos_terminal', 'amount_minor' => 30000, 'reference' => null]],
        ]);

        $response->assertSessionHasErrors('payments.0.reference');
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkoutId]);
    }

    public function test_blocks_a_staff_member_without_sales_create(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->grantShopAccess($productStaff, $shop->id);

        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id, 'cashier_staff_id' => $productStaff->id]);

        $response = $this->actingAs($productStaff, 'staff')->post("/admin/sales/checkout/{$checkout->id}/collect-payment", [
            'payments' => [['method' => 'cash', 'amount_minor' => 10000, 'reference' => null]],
        ]);

        $response->assertForbidden();
    }
}
