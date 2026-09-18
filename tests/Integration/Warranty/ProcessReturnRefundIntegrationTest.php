<?php

declare(strict_types=1);

namespace Tests\Integration\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Application\Commands\ApproveReturnRequestCommand;
use Domain\Warranty\Application\Commands\AssessReturnRequestCommand;
use Domain\Warranty\Application\Commands\ProcessReturnRefundCommand;
use Domain\Warranty\Application\Handlers\ApproveReturnRequestHandler;
use Domain\Warranty\Application\Handlers\AssessReturnRequestHandler;
use Domain\Warranty\Application\Handlers\ProcessReturnRefundHandler;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReturnRefundIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_a_return_refund_confirms_the_payment_and_resolves_the_return(): void
    {
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'requested',
        ]);

        app(AssessReturnRequestHandler::class)->handle(new AssessReturnRequestCommand(
            returnRequestId: $returnRequest->id,
            assessedByStaffId: $staff->id,
        ));

        app(ApproveReturnRequestHandler::class)->handle(new ApproveReturnRequestCommand(
            returnRequestId: $returnRequest->id,
            approvedByStaffId: $staff->id,
        ));

        app(ProcessReturnRefundHandler::class)->handle(new ProcessReturnRefundCommand(
            returnRequestId: $returnRequest->id,
            paymentTransactionId: $sale->payment_transaction_id,
            processedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('return_requests', [
            'id' => $returnRequest->id,
            'resolution_state' => 'resolved',
            'refund_transaction_id' => $sale->payment_transaction_id,
        ]);

        $this->assertDatabaseHas('payments_transactions', [
            'id' => $sale->payment_transaction_id,
            'status' => 'refunded',
        ]);
    }
}
