<?php

declare(strict_types=1);

namespace Tests\Integration\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Application\Commands\CreateWarrantyClaimCommand;
use Domain\Warranty\Application\Handlers\CreateWarrantyClaimHandler;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimSource;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> real repository/lookup -> real DB, no HTTP (CPNC §6's Integration tier). */
class CreateWarrantyClaimIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_claim_can_be_submitted_against_the_customers_own_sale(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);
        $policy = WarrantyPolicyRecord::factory()->create();

        $id = app(CreateWarrantyClaimHandler::class)->handle(new CreateWarrantyClaimCommand(
            warrantyPolicyId: $policy->id,
            originatingSaleId: $sale->id,
            originatingRepairJobId: null,
            customerId: $customer->id,
            submittedByStaffId: null,
        ));

        $this->assertDatabaseHas('warranty_claims', [
            'id' => $id,
            'warranty_policy_id' => $policy->id,
            'originating_sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'submitted',
        ]);
    }

    public function test_a_claim_cannot_be_submitted_against_another_customers_sale(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);
        $policy = WarrantyPolicyRecord::factory()->create();

        try {
            app(CreateWarrantyClaimHandler::class)->handle(new CreateWarrantyClaimCommand(
                warrantyPolicyId: $policy->id,
                originatingSaleId: $sale->id,
                originatingRepairJobId: null,
                customerId: $intruder->id,
                submittedByStaffId: null,
            ));
            $this->fail('Expected InvalidWarrantyClaimSource to be thrown.');
        } catch (InvalidWarrantyClaimSource) {
            // expected
        }

        $this->assertDatabaseMissing('warranty_claims', ['originating_sale_id' => $sale->id, 'customer_id' => $intruder->id]);
    }

    public function test_a_claim_cannot_be_submitted_without_an_originating_sale_or_repair(): void
    {
        $customer = CustomerRecord::factory()->create();
        $policy = WarrantyPolicyRecord::factory()->create();

        $this->expectException(InvalidWarrantyClaimSource::class);

        app(CreateWarrantyClaimHandler::class)->handle(new CreateWarrantyClaimCommand(
            warrantyPolicyId: $policy->id,
            originatingSaleId: null,
            originatingRepairJobId: null,
            customerId: $customer->id,
            submittedByStaffId: null,
        ));
    }
}
