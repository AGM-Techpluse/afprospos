<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanApplyDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_apply_a_discount_to_an_open_checkout(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashier->id,
            'subtotal_minor' => 10000,
            'total_minor' => 10000,
        ]);
        SalesCheckoutItemRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'unit_price_minor' => 10000, 'quantity' => 1]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/discount", [
            'type' => 'promotion',
            'source_id' => 1,
            'amount_minor' => 1000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_checkout_adjustments', [
            'sales_checkout_id' => $checkout->id,
            'type' => 'promotion',
            'amount_minor' => 1000,
            'applied_order' => 1,
        ]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'discount_minor' => 1000, 'total_minor' => 9000]);
    }

    public function test_applying_a_second_discount_replaces_the_first_non_stackable_default(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashier->id,
            'subtotal_minor' => 10000,
            'total_minor' => 10000,
        ]);
        SalesCheckoutItemRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'unit_price_minor' => 10000, 'quantity' => 1]);

        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/discount", [
            'type' => 'promotion', 'source_id' => 1, 'amount_minor' => 1000,
        ]);
        $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/discount", [
            'type' => 'store_credit', 'source_id' => 2, 'amount_minor' => 2000,
        ]);

        $this->assertDatabaseCount('sales_checkout_adjustments', 1);
        $this->assertDatabaseHas('sales_checkout_adjustments', ['sales_checkout_id' => $checkout->id, 'type' => 'store_credit', 'amount_minor' => 2000]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'discount_minor' => 2000, 'total_minor' => 8000]);
    }
}
