<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Dashboard;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardShowsRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_surfaces_the_customers_open_repair(): void
    {
        $customer = CustomerRecord::factory()->create();
        RepairJobRecord::factory()->create([
            'customer_id' => $customer->id,
            'device_make' => 'Tecno',
            'device_model' => 'Spark 3',
            'repair_status' => 'received',
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/customer/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('activeRepair.device', 'Tecno Spark 3')
                ->where('activeRepair.status_label', 'Received'));
    }

    public function test_dashboard_ignores_a_completed_repair(): void
    {
        $customer = CustomerRecord::factory()->create();
        RepairJobRecord::factory()->create([
            'customer_id' => $customer->id,
            'repair_status' => 'completed',
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/customer/dashboard')
            ->assertInertia(fn ($page) => $page->where('activeRepair', null));
    }

    public function test_dashboard_lists_the_customers_recent_sale(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')
            ->get('/customer/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('recentOrders.0.id', $sale->id)
                ->where('recentOrders.0.type', 'sale'));
    }
}
