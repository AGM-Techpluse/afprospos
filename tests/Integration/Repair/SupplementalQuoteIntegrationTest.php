<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Application\Commands\DecideSupplementalQuoteCommand;
use Domain\Repair\Application\Commands\RequestRepairPaymentApprovalCommand;
use Domain\Repair\Application\Commands\RequestSupplementalQuoteCommand;
use Domain\Repair\Application\Handlers\DecideSupplementalQuoteHandler;
use Domain\Repair\Application\Handlers\RequestRepairPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\RequestSupplementalQuoteHandler;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\Exceptions\PaymentAmountDoesNotMatchOutstandingBalance;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairSupplementalQuoteRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A "supplement" — additional cost discovered after the original quote was
 * authorized. Confirms the one integration point that makes the feature
 * coherent: an approved supplemental quote is summed into
 * RepairJobDetailQuery's quote_total_minor/outstanding_balance_minor AND
 * RequestRepairPaymentApprovalHandler::expectedOutstandingMinor(), so the
 * existing "request payment recording" flow automatically reflects it
 * without any change to that flow itself.
 */
class SupplementalQuoteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_while_the_job_is_not_yet_in_progress_is_rejected(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'awaiting_authorization',
            'repair_cost_minor' => 900000,
        ]);

        $this->expectException(InvalidRepairTransition::class);

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand(
            repairJobId: $job->id,
            amountMinor: 50000,
            reason: 'Extra parts needed',
            requestedByStaffId: $staff->id,
        ));
    }

    public function test_approving_a_supplemental_quote_raises_the_quote_total_and_outstanding_balance(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'customer_id' => CustomerRecord::factory()->create()->id,
            'shop_id' => ShopRecord::factory()->create()->id,
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 9000000,
        ]);

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand(
            repairJobId: $job->id,
            amountMinor: 14692500,
            reason: 'Battery, screen and charging port all needed replacing',
            requestedByStaffId: $staff->id,
        ));

        $quote = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();
        $this->assertSame('pending', $quote->status);

        app(DecideSupplementalQuoteHandler::class)->handle(new DecideSupplementalQuoteCommand(
            quoteId: $quote->id,
            approved: true,
            decidedByStaffId: $staff->id,
        ));

        $detail = app(RepairJobDetailQuery::class)->find($job->id);
        $this->assertSame(23692500, $detail['quote_total_minor']);
        $this->assertSame(23692500, $detail['outstanding_balance_minor']);
        $this->assertNull($detail['pending_supplemental_quote']);
        $this->assertCount(1, $detail['supplemental_quotes']);
        $this->assertSame('approved', $detail['supplemental_quotes'][0]['status']);

        // The payment-approval flow (unchanged itself) now validates against the corrected total.
        $job->update(['repair_status' => 'handover']);

        app(RequestRepairPaymentApprovalHandler::class)->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $job->id,
            requestedFinancialStatus: 'fully_paid',
            amountMinor: 23692500,
            method: 'cash',
            requestedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_payment_approvals', [
            'repair_job_id' => $job->id,
            'amount_minor' => 23692500,
            'status' => 'pending',
        ]);
    }

    public function test_the_stale_pre_supplement_amount_is_rejected_once_a_supplement_is_approved(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 9000000,
        ]);

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand(
            repairJobId: $job->id,
            amountMinor: 14692500,
            reason: 'Extra parts',
            requestedByStaffId: $staff->id,
        ));
        $quote = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();
        app(DecideSupplementalQuoteHandler::class)->handle(new DecideSupplementalQuoteCommand($quote->id, true, $staff->id));

        $job->update(['repair_status' => 'handover']);

        $this->expectException(PaymentAmountDoesNotMatchOutstandingBalance::class);

        app(RequestRepairPaymentApprovalHandler::class)->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $job->id,
            requestedFinancialStatus: 'fully_paid',
            amountMinor: 9000000,
            method: 'cash',
            requestedByStaffId: $staff->id,
        ));
    }

    public function test_rejecting_a_supplemental_quote_leaves_the_quote_total_unchanged(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 9000000,
        ]);

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand(
            repairJobId: $job->id,
            amountMinor: 14692500,
            reason: 'Extra parts',
            requestedByStaffId: $staff->id,
        ));
        $quote = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        app(DecideSupplementalQuoteHandler::class)->handle(new DecideSupplementalQuoteCommand($quote->id, false, $staff->id));

        $detail = app(RepairJobDetailQuery::class)->find($job->id);
        $this->assertSame(9000000, $detail['quote_total_minor']);
        $this->assertDatabaseHas('repair_supplemental_quotes', ['id' => $quote->id, 'status' => 'rejected']);
    }

    public function test_a_rejected_request_can_be_re_requested_as_a_new_row(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 9000000,
        ]);

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand($job->id, 50000, 'First attempt', $staff->id));
        $first = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();
        app(DecideSupplementalQuoteHandler::class)->handle(new DecideSupplementalQuoteCommand($first->id, false, $staff->id));

        app(RequestSupplementalQuoteHandler::class)->handle(new RequestSupplementalQuoteCommand($job->id, 60000, 'Revised amount', $staff->id));

        $this->assertSame(2, RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->count());
        $this->assertSame(
            60000,
            RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->where('status', 'pending')->value('amount_minor'),
        );
    }
}
