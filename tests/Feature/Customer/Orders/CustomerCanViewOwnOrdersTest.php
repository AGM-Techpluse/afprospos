<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Orders;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCanViewOwnOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_sees_their_own_open_checkout_and_completed_sale(): void
    {
        $customer = CustomerRecord::factory()->create();
        SalesCheckoutRecord::factory()->create(['customer_id' => $customer->id, 'status' => 'open']);
        SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer, 'customer')->get('/customer/orders');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('orders.data', 2));
    }

    public function test_a_customer_can_view_their_own_open_checkout_detail(): void
    {
        $customer = CustomerRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['customer_id' => $customer->id, 'status' => 'open']);

        $response = $this->actingAs($customer, 'customer')->get("/customer/orders/checkouts/{$checkout->id}");

        $response->assertOk();
    }

    public function test_a_customer_can_view_their_own_completed_sale_detail(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer, 'customer')->get("/customer/orders/sales/{$sale->id}");

        $response->assertOk();
    }
}
