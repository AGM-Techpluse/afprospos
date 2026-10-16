<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Handlers\InitiatePaymentHandler;
use Domain\Repair\Application\Commands\DecideRepairPaymentApprovalCommand;
use Domain\Repair\Application\Commands\RequestRepairPaymentApprovalCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Handlers\DecideRepairPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\RequestRepairPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPaymentApprovalRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage: a fully_paid approval's amount is validated to already
 * include any outstanding approved diagnosis fee folded into the quote
 * (RequestRepairPaymentApprovalHandler::expectedOutstandingMinor()) — approving
 * it must split that portion into its own repair_diagnosis_fee transaction, not
 * silently fold it into repair_final_payment, or the diagnosis fee's own `paid`
 * flag stays false forever even though the money was collected.
 */
class DecideRepairPaymentApprovalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_fully_paid_request_splits_the_folded_diagnosis_fee_into_its_own_transaction(): void
    {
        $manager = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => CustomerRecord::factory()->create()->id,
            'repair_status' => 'awaiting_authorization',
            'financial_status' => 'unpaid',
            'repair_cost_minor' => 800000,
        ]);

        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($job->id, 40000, $manager->id));
        $job->update(['repair_status' => 'completed']);

        app(RequestRepairPaymentApprovalHandler::class)->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $job->id,
            requestedFinancialStatus: 'fully_paid',
            amountMinor: 840000, // repair_cost_minor + the outstanding diagnosis fee, folded into the quote
            method: 'cash',
            requestedByStaffId: $manager->id,
        ));

        $approval = RepairPaymentApprovalRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        app(DecideRepairPaymentApprovalHandler::class)->handle(new DecideRepairPaymentApprovalCommand(
            approvalId: $approval->id,
            approved: true,
            decidedByStaffId: $manager->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'financial_status' => 'fully_paid']);

        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $job->id,
            'purpose' => 'repair_diagnosis_fee',
            'amount_minor' => 40000,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $job->id,
            'purpose' => 'repair_final_payment',
            'amount_minor' => 800000,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_partially_paid_down_payment_approval_is_never_split_against_the_diagnosis_fee(): void
    {
        $manager = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => CustomerRecord::factory()->create()->id,
            'repair_status' => 'awaiting_authorization',
            'financial_status' => 'unpaid',
            'repair_cost_minor' => 800000,
        ]);

        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($job->id, 40000, $manager->id));
        $job->update(['repair_status' => 'completed']);

        app(RequestRepairPaymentApprovalHandler::class)->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $job->id,
            requestedFinancialStatus: 'partially_paid',
            amountMinor: 200000,
            method: 'cash',
            requestedByStaffId: $manager->id,
        ));

        $approval = RepairPaymentApprovalRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        app(DecideRepairPaymentApprovalHandler::class)->handle(new DecideRepairPaymentApprovalCommand(
            approvalId: $approval->id,
            approved: true,
            decidedByStaffId: $manager->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'financial_status' => 'partially_paid']);
        $this->assertDatabaseMissing('payments_transactions', ['payable_id' => $job->id, 'purpose' => 'repair_diagnosis_fee']);
        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $job->id,
            'purpose' => 'repair_final_payment',
            'amount_minor' => 200000,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_fee_already_paid_at_the_diagnosis_case_stage_is_not_double_charged_or_double_split(): void
    {
        $manager = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $case = DiagnosisCaseRecord::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'created_by_staff_id' => $manager->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
            'status' => 'diagnosed',
            'outcome' => 'repairable',
            'diagnosis_fee_minor' => 40000,
        ]);

        // The fee was collected against the diagnosis case itself, before any repair job existed.
        app(InitiatePaymentHandler::class)->handle(new InitiatePaymentCommand(
            payableType: 'diagnosis_case',
            payableId: $case->id,
            purpose: 'repair_diagnosis_fee',
            method: 'cash',
            amountMinor: 40000,
            initiatedByStaffId: $manager->id,
        ));

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'diagnosis_case_id' => $case->id,
            'repair_status' => 'awaiting_authorization',
            'financial_status' => 'unpaid',
            'repair_cost_minor' => 800000,
        ]);

        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($job->id, 40000, $manager->id));
        $job->update(['repair_status' => 'completed']);

        app(RequestRepairPaymentApprovalHandler::class)->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $job->id,
            requestedFinancialStatus: 'fully_paid',
            amountMinor: 800000, // repair_cost_minor only — the fee is already paid, not folded in.
            method: 'cash',
            requestedByStaffId: $manager->id,
        ));

        $approval = RepairPaymentApprovalRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        app(DecideRepairPaymentApprovalHandler::class)->handle(new DecideRepairPaymentApprovalCommand(
            approvalId: $approval->id,
            approved: true,
            decidedByStaffId: $manager->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'financial_status' => 'fully_paid']);
        $this->assertDatabaseMissing('payments_transactions', ['payable_type' => 'repair_job', 'payable_id' => $job->id, 'purpose' => 'repair_diagnosis_fee']);
        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $job->id,
            'purpose' => 'repair_final_payment',
            'amount_minor' => 800000,
            'status' => 'confirmed',
        ]);

        $detail = app(RepairJobDetailQuery::class)->find($job->id);
        $this->assertTrue($detail['diagnosis_fee']['paid']);
        $this->assertSame('cash', $detail['diagnosis_fee']['method']);
    }
}
