<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Orders;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A "Cannot" test asserts both the rejection and that nothing about the record changed, per CLAUDE.md's own rule. */
class CustomerCannotViewAnotherCustomersOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_view_another_customers_open_checkout(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['customer_id' => $owner->id, 'status' => 'open']);

        $response = $this->actingAs($intruder, 'customer')->get("/customer/orders/checkouts/{$checkout->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'customer_id' => $owner->id]);
    }

    public function test_a_customer_cannot_view_another_customers_completed_sale(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);

        $response = $this->actingAs($intruder, 'customer')->get("/customer/orders/sales/{$sale->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_sales', ['id' => $sale->id, 'customer_id' => $owner->id]);
    }
}
