<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanCreateShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_shops_create_create_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->post('/admin/shops', [
            'name' => 'Downtown Branch',
            'sku_prefix_code' => 'dtb',
            'address' => '1 Market Street',
            'contact_phone' => '08011111111',
            'contact_email' => 'downtown@example.test',
        ]);

        $response->assertRedirect('/admin/shops');
        $this->assertDatabaseHas('shops', [
            'name' => 'Downtown Branch',
            'sku_prefix_code' => 'DTB',
        ]);
    }

    public function test_rejects_a_sku_prefix_code_already_used_by_another_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->create(['sku_prefix_code' => 'DTB']);

        $response = $this->actingAs($owner, 'staff')->post('/admin/shops', [
            'name' => 'Second Branch',
            'sku_prefix_code' => 'dtb',
            'address' => '2 Market Street',
            'contact_phone' => '08022222222',
            'contact_email' => 'second@example.test',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('shops', ['name' => 'Second Branch']);
    }

    public function test_blocks_a_staff_member_without_shops_create_from_creating_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no shops.* permissions per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/shops', [
            'name' => 'Unauthorized Branch',
            'sku_prefix_code' => 'unb',
            'address' => '3 Market Street',
            'contact_phone' => '08033333333',
            'contact_email' => 'unauthorized@example.test',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('shops', ['name' => 'Unauthorized Branch']);
    }
}
