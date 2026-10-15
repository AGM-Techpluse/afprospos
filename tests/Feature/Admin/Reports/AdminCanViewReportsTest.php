<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Reports;

use Database\Seeders\RolePermissionSeeder;
use Domain\Commission\Infrastructure\Persistence\Eloquent\CommissionLedgerEntryRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Shop\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanViewReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_the_reports_hub_with_module_cards(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Index')
                ->has('cards.repairs')
                ->has('cards.sales')
                ->has('cards.inventory')
                ->has('cards.staff')
                ->has('cards.commission'));
    }

    public function test_staff_without_reports_permission_cannot_open_reports(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = StaffRecord::factory()->create();

        $this->actingAs($staff, 'staff')
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_repair_report_distinguishes_cash_collected_earned_and_refund_due(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $repair = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'failed_requires_resolution',
            'failure_service_charge_minor' => 100000,
            'failure_refund_due_minor' => 200000,
        ]);

        PaymentTransactionRecord::factory()->create([
            'payable_type' => 'repair_job',
            'payable_id' => $repair->id,
            'purpose' => 'repair_down_payment',
            'amount_minor' => 300000,
            'status' => 'confirmed',
        ]);

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/repairs?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Repairs')
                ->where('report.summary.cash_collected_minor', 300000)
                ->where('report.summary.earned_minor', 100000)
                ->where('report.summary.refund_due_minor', 200000));
    }

    public function test_sales_and_inventory_reports_read_operational_totals(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
        SaleRecord::factory()->create([
            'sales_checkout_id' => $checkout->id,
            'shop_id' => $shop->id,
            'total_minor' => 500000,
        ]);

        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 200000]);
        InventoryStockLevelRecord::factory()->create([
            'sku_id' => $sku->id,
            'shop_id' => $shop->id,
            'on_hand' => 4,
            'reserved' => 1,
        ]);

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/sales?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('report.summary.gross_sales_minor', 500000));

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/inventory?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.summary.on_hand_units', 4)
                ->where('report.summary.reserved_units', 1)
                ->where('report.summary.available_units', 3));
    }

    public function test_sales_report_lists_real_paginated_sales_past_the_old_ten_row_cap(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            $checkout = SalesCheckoutRecord::factory()->create(['shop_id' => $shop->id]);
            SaleRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'shop_id' => $shop->id]);
        }

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/sales?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sales_list.total', 25)
                ->where('sales_list.current_page', 1)
                ->has('sales_list.data', 20));

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/sales?shop_id='.$shop->id.'&page=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sales_list.current_page', 2)
                ->has('sales_list.data', 5));
    }

    public function test_repairs_report_lists_real_paginated_repairs_past_the_old_twenty_row_cap(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        RepairJobRecord::factory()->count(25)->create(['shop_id' => $shop->id]);

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/repairs?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('repair_list.total', 25)
                ->has('repair_list.data', 20));
    }

    public function test_inventory_report_lists_real_paginated_low_stock_past_the_old_twenty_row_cap(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            $sku = SkuRecord::factory()->nonSerialized()->create(['low_stock_threshold' => 5]);
            InventoryStockLevelRecord::factory()->create([
                'sku_id' => $sku->id,
                'shop_id' => $shop->id,
                'on_hand' => 2,
                'reserved' => 0,
            ]);
        }

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/inventory?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.summary.low_stock_count', 25)
                ->where('low_stock_list.total', 25)
                ->has('low_stock_list.data', 20));
    }

    public function test_staff_report_shows_commission_earned_by_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        CommissionLedgerEntryRecord::query()->create([
            'staff_id' => $technician->id,
            'source_type' => 'repair',
            'source_id' => 1,
            'commissionable_amount_minor' => 150000,
            'rate_snapshot_percent' => 100.00,
            'role_snapshot' => 'Technician',
            'entry_type' => 'earned',
            'status' => 'earned',
            'shop_id' => $shop->id,
        ]);

        $this->actingAs($owner, 'staff')
            ->get('/admin/reports/staff?shop_id='.$shop->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('commission_by_staff.0.staff_id', $technician->id)
                ->where('commission_by_staff.0.total_minor', 150000)
                ->where('commission_by_staff.0.entries_count', 1));
    }
}
