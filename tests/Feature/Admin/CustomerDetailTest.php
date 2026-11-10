<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_staff_member_sees_a_customers_orders_and_repairs(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $customer = CustomerRecord::factory()->create(['name' => 'Purchase History Pat']);

        SaleRecord::factory()->create(['customer_id' => $customer->id, 'total_minor' => 50000]);
        SaleRecord::factory()->create(['customer_id' => $customer->id, 'total_minor' => 30000]);
        SalesCheckoutRecord::factory()->create(['customer_id' => $customer->id, 'status' => 'open']);
        RepairJobRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/customers/{$customer->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Show')
            ->where('customer.name', 'Purchase History Pat')
            ->where('orderSummary.order_count', 2)
            ->where('orderSummary.total_spent_minor', 80000)
            ->where('repairCount', 1)
            ->where('orders.total', 3)
            ->where('repairs.total', 1));
    }

    public function test_a_customer_with_no_history_shows_empty_states_not_an_error(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')->get("/admin/customers/{$customer->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('orderSummary.order_count', 0)
            ->where('orderSummary.total_spent_minor', 0)
            ->where('repairCount', 0));
    }

    public function test_an_unknown_customer_is_a_404(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')->get('/admin/customers/999999')->assertNotFound();
    }

    public function test_a_staff_member_without_customers_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketing = StaffRecord::factory()->create();
        $marketing->assignRole('Marketing Staff');
        $customer = CustomerRecord::factory()->create();

        $this->actingAs($marketing, 'staff')->get("/admin/customers/{$customer->id}")->assertForbidden();
    }
}
