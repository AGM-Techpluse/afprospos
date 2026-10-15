<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Reports;

use Database\Seeders\RolePermissionSeeder;
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
                ->has('cards.staff'));
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
}
