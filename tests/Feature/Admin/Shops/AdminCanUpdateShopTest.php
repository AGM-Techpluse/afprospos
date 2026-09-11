<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanUpdateShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_shops_edit_update_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create(['name' => 'Original Branch']);

        $response = $this->actingAs($owner, 'staff')->put("/admin/shops/{$shop->id}", [
            'name' => 'Updated Branch',
            'sku_prefix_code' => $shop->sku_prefix_code,
            'address' => 'New Address',
            'contact_phone' => $shop->contact_phone,
            'contact_email' => $shop->contact_email,
        ]);

        $response->assertRedirect("/admin/shops/{$shop->id}");
        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Updated Branch',
            'address' => 'New Address',
        ]);
    }

    public function test_rejects_a_sku_prefix_code_already_used_by_another_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->create(['sku_prefix_code' => 'TKN']);
        $shop = ShopRecord::factory()->create(['name' => 'Original Branch', 'sku_prefix_code' => 'ORB']);

        $response = $this->actingAs($owner, 'staff')->put("/admin/shops/{$shop->id}", [
            'name' => 'Updated Branch',
            'sku_prefix_code' => 'tkn',
            'address' => $shop->address,
            'contact_phone' => $shop->contact_phone,
            'contact_email' => $shop->contact_email,
        ]);

        $response->assertSessionHasErrors('sku_prefix_code');
        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Original Branch',
            'sku_prefix_code' => 'ORB',
        ]);
    }

    public function test_blocks_a_staff_member_without_shops_edit_from_updating_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no shops.* permissions per config/afprospos.php

        $shop = ShopRecord::factory()->create(['name' => 'Original Branch']);

        $response = $this->actingAs($cashier, 'staff')->put("/admin/shops/{$shop->id}", [
            'name' => 'Hacked Branch',
            'sku_prefix_code' => $shop->sku_prefix_code,
            'address' => $shop->address,
            'contact_phone' => $shop->contact_phone,
            'contact_email' => $shop->contact_email,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Original Branch',
        ]);
    }
}
