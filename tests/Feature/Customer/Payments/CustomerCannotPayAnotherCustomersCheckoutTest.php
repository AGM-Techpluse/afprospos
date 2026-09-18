<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Payments;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A "Cannot" test asserts both the rejection and that nothing about the record changed, per CLAUDE.md's own rule. */
class CustomerCannotPayAnotherCustomersCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_request_a_bank_transfer_for_another_customers_checkout(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['customer_id' => $owner->id, 'status' => 'open']);

        $response = $this->actingAs($intruder, 'customer')->post(
            "/customer/payments/checkouts/{$checkout->id}/bank-transfer",
            ['transfer_reference' => 'TXN-INTRUDER'],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('payments_transactions', ['payable_type' => 'sales_checkout', 'payable_id' => $checkout->id]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'open']);
    }

    public function test_viewing_the_payment_page_for_another_customers_checkout_is_forbidden(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['customer_id' => $owner->id, 'status' => 'open']);

        $response = $this->actingAs($intruder, 'customer')->get("/customer/payments/checkouts/{$checkout->id}");

        $response->assertForbidden();
    }
}
