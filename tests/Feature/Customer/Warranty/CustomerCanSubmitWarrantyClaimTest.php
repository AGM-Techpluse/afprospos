<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCanSubmitWarrantyClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_submit_a_claim_for_their_own_sale(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);
        $policy = WarrantyPolicyRecord::factory()->create();

        $response = $this->actingAs($customer, 'customer')
            ->post('/customer/warranty/claims', [
                'warranty_policy_id' => $policy->id,
                'originating_sale_id' => $sale->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warranty_claims', [
            'warranty_policy_id' => $policy->id,
            'originating_sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'submitted',
        ]);
    }

    public function test_a_customer_cannot_submit_a_claim_for_another_customers_sale(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);
        $policy = WarrantyPolicyRecord::factory()->create();

        $response = $this->actingAs($intruder, 'customer')
            ->post('/customer/warranty/claims', [
                'warranty_policy_id' => $policy->id,
                'originating_sale_id' => $sale->id,
            ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('warranty_claims', ['originating_sale_id' => $sale->id, 'customer_id' => $intruder->id]);
    }
}
