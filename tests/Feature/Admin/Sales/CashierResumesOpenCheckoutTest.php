<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Returning to Sales/POS with no explicit checkout ID should not silently abandon an already-open checkout. */
class CashierResumesOpenCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_sales_checkout_with_no_id_resumes_the_cashiers_own_open_checkout_at_the_active_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        StaffShopGrantRecord::query()->create(['staff_id' => $cashier->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashier->id,
            'status' => 'open',
        ]);

        // Establishes the active shop for this session, matching normal shop-switcher usage.
        $this->actingAs($cashier, 'staff')->post('/admin/shops/switch', ['shop_id' => $shop->id]);

        $response = $this->actingAs($cashier, 'staff')->get('/admin/sales/checkout');

        $response->assertRedirect("/admin/sales/checkout?checkout={$checkout->id}");
    }

    public function test_visiting_sales_checkout_with_no_open_checkout_shows_the_new_checkout_screen(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        StaffShopGrantRecord::query()->create(['staff_id' => $cashier->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        $this->actingAs($cashier, 'staff')->post('/admin/shops/switch', ['shop_id' => $shop->id]);

        $response = $this->actingAs($cashier, 'staff')->get('/admin/sales/checkout');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Sales/Checkout')->where('checkout', null));
    }

    public function test_a_cashier_never_gets_auto_resumed_into_a_different_cashiers_open_checkout_at_the_same_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();

        $cashierA = StaffRecord::factory()->create();
        $cashierA->assignRole('Cashier');
        StaffShopGrantRecord::query()->create(['staff_id' => $cashierA->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        $cashierB = StaffRecord::factory()->create();
        $cashierB->assignRole('Cashier');
        StaffShopGrantRecord::query()->create(['staff_id' => $cashierB->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        // Cashier A already has an open checkout at this shop (e.g. a second till/register).
        SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $cashierA->id,
            'status' => 'open',
        ]);

        $this->actingAs($cashierB, 'staff')->post('/admin/shops/switch', ['shop_id' => $shop->id]);

        $response = $this->actingAs($cashierB, 'staff')->get('/admin/sales/checkout');

        // Cashier B has no open checkout of their own, so they land on the empty
        // "New sale" screen — never redirected into cashier A's still-open checkout.
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Sales/Checkout')->where('checkout', null));
    }
}
