<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanDeactivateAndReactivateShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_shops_edit_deactivate_and_reactivate_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create(['status' => 'active']);

        $deactivateResponse = $this->actingAs($owner, 'staff')->post("/admin/shops/{$shop->id}/deactivate");
        $deactivateResponse->assertRedirect("/admin/shops/{$shop->id}");
        $this->assertDatabaseHas('shops', ['id' => $shop->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Shop', 'event_type' => 'ShopDeactivated']);

        $activateResponse = $this->actingAs($owner, 'staff')->post("/admin/shops/{$shop->id}/activate");
        $activateResponse->assertRedirect("/admin/shops/{$shop->id}");
        $this->assertDatabaseHas('shops', ['id' => $shop->id, 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Shop', 'event_type' => 'ShopActivated']);
    }

    public function test_deactivating_an_already_inactive_shop_fails(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create(['status' => 'inactive']);

        $this->actingAs($owner, 'staff')->post("/admin/shops/{$shop->id}/deactivate")
            ->assertStatus(500);

        $this->assertDatabaseHas('shops', ['id' => $shop->id, 'status' => 'inactive']);
    }

    public function test_blocks_a_staff_member_without_shops_edit_from_deactivating_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no shops.* permissions per config/afprospos.php

        $shop = ShopRecord::factory()->create(['status' => 'active']);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/shops/{$shop->id}/deactivate");

        $response->assertForbidden();
        $this->assertDatabaseHas('shops', ['id' => $shop->id, 'status' => 'active']);
    }
}
