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

class CashierCanOpenConcurrentCheckoutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_new_opens_a_second_checkout_instead_of_reusing_the_open_one(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        StaffShopGrantRecord::query()->create(['staff_id' => $cashier->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        $first = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id, 'cashier_staff_id' => $cashier->id, 'status' => 'open']);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', [
            'shop_id' => $shop->id,
            'force_new' => true,
        ]);

        $response->assertRedirect();
        $this->assertSame(2, SalesCheckoutRecord::query()->where('cashier_staff_id', $cashier->id)->where('status', 'open')->count());
        $this->assertStringEndsWith(
            '/admin/sales/checkout?checkout='.SalesCheckoutRecord::query()->where('cashier_staff_id', $cashier->id)->latest('id')->first()->id,
            $response->headers->get('Location'),
        );
        // The original checkout is untouched, still open.
        $this->assertDatabaseHas('sales_checkouts', ['id' => $first->id, 'status' => 'open']);
    }

    public function test_visiting_the_checkout_screen_with_two_open_checkouts_shows_a_chooser_instead_of_redirecting(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        StaffShopGrantRecord::query()->create(['staff_id' => $cashier->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id, 'cashier_staff_id' => $cashier->id, 'status' => 'open']);
        SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id, 'cashier_staff_id' => $cashier->id, 'status' => 'open']);

        $response = $this->actingAs($cashier, 'staff')->get('/admin/sales/checkout');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Sales/Checkout')
            ->where('checkout', null)
            ->has('openCheckouts', 2));
    }

    public function test_without_force_new_a_second_create_still_resumes_the_existing_open_checkout(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        StaffShopGrantRecord::query()->create(['staff_id' => $cashier->id, 'shop_id' => $shop->id, 'granted_at' => now()]);

        $existing = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id, 'cashier_staff_id' => $cashier->id, 'status' => 'open']);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/sales/checkout', ['shop_id' => $shop->id]);

        $response->assertRedirect("/admin/sales/checkout?checkout={$existing->id}");
        $this->assertSame(1, SalesCheckoutRecord::query()->where('cashier_staff_id', $cashier->id)->count());
    }
}
