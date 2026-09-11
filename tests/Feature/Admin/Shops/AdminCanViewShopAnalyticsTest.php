<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanViewShopAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shop_detail_page_shows_real_stock_sales_and_revenue_numbers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 12, 'reserved' => 0]);
        SaleRecord::factory()->create(['shop_id' => $shop->id, 'total_minor' => 50000]);
        SaleRecord::factory()->create(['shop_id' => $shop->id, 'total_minor' => 25000]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/shops/{$shop->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Shops/Show')
            ->where('analytics.stock_units', 12)
            ->where('analytics.sales_count', 2)
            ->where('analytics.revenue_minor', 75000)
            ->has('analytics.revenue_series', 14));
    }

    public function test_staff_count_includes_granted_staff_and_shop_owners(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        StaffShopGrantRecord::query()->create([
            'staff_id' => $cashier->id,
            'shop_id' => $shop->id,
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/shops/{$shop->id}");

        $response->assertOk();
        // The owner counts toward every shop (implicit all-shop access) plus the explicitly granted cashier.
        $response->assertInertia(fn ($page) => $page->where('analytics.staff_count', 2));
    }
}
