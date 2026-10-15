<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Shops;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

    public function test_the_revenue_chart_series_includes_repair_revenue_alongside_sales(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create();

        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
        PaymentTransactionRecord::factory()->create([
            'payable_type' => 'sales_checkout',
            'payable_id' => $checkout->id,
            'amount_minor' => 40000,
        ]);

        $repairJob = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);
        PaymentTransactionRecord::factory()->create([
            'payable_type' => 'repair_job',
            'payable_id' => $repairJob->id,
            'amount_minor' => 30000,
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/shops/{$shop->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Shops/Show')
            ->where(
                'analytics.revenue_series',
                fn (Collection $series) => $series->sum('sales_minor') === 40000 && $series->sum('repair_minor') === 30000
            )
            ->where(
                'analytics.revenue_breakdown',
                fn (Collection $breakdown) => $breakdown->pluck('revenue_minor', 'stream')->all() === [
                    'sales_checkout' => 40000,
                    'repair_job' => 30000,
                ]
            ));
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

    public function test_a_warehouses_detail_page_still_reports_its_real_stock_and_staff_counts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $warehouse = ShopRecord::factory()->create(['is_warehouse' => true]);
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $warehouse->id, 'on_hand' => 40, 'reserved' => 0]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/shops/{$warehouse->id}");

        $response->assertOk();
        // A warehouse can never have a sale or a repair, but it genuinely holds stock, so that figure is real, not suppressed.
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Shops/Show')
            ->where('shop.is_warehouse', true)
            ->where('analytics.stock_units', 40)
            ->where('analytics.sales_count', 0)
            ->where('analytics.revenue_minor', 0));
    }
}
