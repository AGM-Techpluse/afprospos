<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Payments;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCanRequestBankTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_request_a_bank_transfer_for_their_own_open_checkout(): void
    {
        $customer = CustomerRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'open',
            'total_minor' => 15000,
        ]);

        $response = $this->actingAs($customer, 'customer')->post(
            "/customer/payments/checkouts/{$checkout->id}/bank-transfer",
            ['transfer_reference' => 'TXN-CUSTOMER-1'],
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'sales_checkout',
            'payable_id' => $checkout->id,
            'method' => 'bank_transfer',
            'amount_minor' => 15000,
            'status' => 'payment_pending_confirmation',
            'provider_reference' => 'TXN-CUSTOMER-1',
        ]);

        // The checkout itself is untouched — only staff completing the sale marks it paid.
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkout->id, 'status' => 'open']);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Payments',
            'event_type' => 'PaymentInitiated',
            'actor_staff_id' => null,
        ]);
    }
}
