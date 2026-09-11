<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanViewShopDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_view_the_shop_directory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')->get('/admin/shops');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Shops/Index'));
    }

    public function test_search_narrows_the_shop_directory_by_name_or_sku_prefix(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->create(['name' => 'Ikeja Branch', 'sku_prefix_code' => 'IKJ']);
        ShopRecord::factory()->create(['name' => 'Lekki Branch', 'sku_prefix_code' => 'LKI']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/shops?search=Ikeja');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('shops.total', 1)
            ->where('shops.data.0.name', 'Ikeja Branch'));
    }

    public function test_status_filter_narrows_the_shop_directory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->create(['status' => 'active']);
        ShopRecord::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/shops?status=inactive');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('shops.total', 1));
    }

    public function test_shop_directory_paginates_results(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        ShopRecord::factory()->count(25)->create();

        $response = $this->actingAs($owner, 'staff')->get('/admin/shops');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('shops.per_page', 20)
            ->where('shops.current_page', 1)
            ->where('shops.last_page', 2)
            ->where('shops.total', 25)
            ->has('shops.data', 20));
    }
}
