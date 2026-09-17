<?php

declare(strict_types=1);

namespace Tests\Integration\Warranty;

use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Application\Commands\ApproveRefundCommand;
use Domain\Warranty\Application\Handlers\ApproveRefundHandler;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimTransition;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> Payments' PaymentRefundService contract -> real DB, no HTTP. */
class ApproveRefundIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_refund_resolves_the_claim_and_refunds_the_payment(): void
    {
        $staff = StaffRecord::factory()->create();
        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);
        $claim = WarrantyClaimRecord::factory()->create([
            'resolution_state' => 'remedy_selected',
            'selected_remedy' => 'refund',
        ]);

        app(ApproveRefundHandler::class)->handle(new ApproveRefundCommand(
            warrantyClaimId: $claim->id,
            paymentTransactionId: $transaction->id,
            approvedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('warranty_claims', [
            'id' => $claim->id,
            'resolution_state' => 'resolved',
            'remedy_reference_id' => $transaction->id,
        ]);
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'refunded',
        ]);
    }

    public function test_a_claim_that_has_not_selected_the_refund_remedy_cannot_be_approved(): void
    {
        $staff = StaffRecord::factory()->create();
        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);
        $claim = WarrantyClaimRecord::factory()->create(['resolution_state' => 'submitted']);

        try {
            app(ApproveRefundHandler::class)->handle(new ApproveRefundCommand(
                warrantyClaimId: $claim->id,
                paymentTransactionId: $transaction->id,
                approvedByStaffId: $staff->id,
            ));
            $this->fail('Expected InvalidWarrantyClaimTransition to be thrown.');
        } catch (InvalidWarrantyClaimTransition) {
            // expected
        }

        $this->assertDatabaseHas('warranty_claims', ['id' => $claim->id, 'resolution_state' => 'submitted']);
    }
}
