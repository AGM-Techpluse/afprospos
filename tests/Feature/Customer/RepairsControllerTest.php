<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Read-only, scoped-to-self — mirrors Customer\OrdersController's own test coverage. */
class RepairsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_view_their_own_repairs(): void
    {
        $customer = CustomerRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['customer_id' => $customer->id]);

        $indexResponse = $this->actingAs($customer, 'customer')->get('/customer/repairs');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page->component('Customer/Repairs/Index'));

        $showResponse = $this->actingAs($customer, 'customer')->get("/customer/repairs/{$job->id}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page->component('Customer/Repairs/Show')->where('repair.id', $job->id));
    }

    public function test_a_customer_cannot_view_another_customers_repair(): void
    {
        $customer = CustomerRecord::factory()->create();
        $otherCustomer = CustomerRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer, 'customer')->get("/customer/repairs/{$job->id}");

        $response->assertForbidden();
    }
}
