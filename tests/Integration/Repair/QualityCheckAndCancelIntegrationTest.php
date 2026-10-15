<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\CancelRepairCommand;
use Domain\Repair\Application\Commands\FailRepairCommand;
use Domain\Repair\Application\Commands\RecordQualityCheckCommand;
use Domain\Repair\Application\Commands\RequestDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\UpdateQcFailPolicyCommand;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\CancelRepairHandler;
use Domain\Repair\Application\Handlers\FailRepairHandler;
use Domain\Repair\Application\Handlers\RecordQualityCheckHandler;
use Domain\Repair\Application\Handlers\RequestDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\UpdateQcFailPolicyHandler;
use Domain\Repair\Domain\Exceptions\InvalidDiagnosisFeeState;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisFeeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairQcCheckRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QC outcomes, the admin's fail policy, cancellation, quote authorization, and diagnosis fee collection. */
class QualityCheckAndCancelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = StaffRecord::factory()->create();
    }

    public function test_a_failed_quality_check_returns_the_job_to_diagnosis_by_default(): void
    {
        $jobId = $this->job('qc_pending');

        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'failed', 'Still no audio', $this->staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'diagnosing']);
        $this->assertDatabaseHas('repair_qc_checks', ['repair_job_id' => $jobId, 'outcome' => 'failed', 'notes' => 'Still no audio']);
    }

    public function test_a_failed_quality_check_cancels_the_job_when_the_admin_chose_cancel(): void
    {
        app(UpdateQcFailPolicyHandler::class)->handle(new UpdateQcFailPolicyCommand('cancel', $this->staff->id));
        $jobId = $this->job('qc_pending');

        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'failed', 'Battery swells', $this->staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'cancelled']);
    }

    public function test_a_passed_quality_check_moves_the_device_to_handover_not_completed(): void
    {
        $jobId = $this->job('qc_pending');

        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'passed', null, $this->staff->id));

        // Completed is set only when the device is released from collection.
        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'handover']);
    }

    public function test_a_not_testable_device_also_goes_to_handover_with_its_note_kept(): void
    {
        $jobId = $this->job('qc_pending');

        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'not_testable', 'No SIM to test calls', $this->staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'handover']);
        $this->assertDatabaseHas('repair_qc_checks', ['repair_job_id' => $jobId, 'outcome' => 'not_testable']);
    }

    public function test_every_quality_check_is_kept_as_history(): void
    {
        $jobId = $this->job('qc_pending');

        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'failed', 'First attempt', $this->staff->id));
        $this->assertSame(1, RepairQcCheckRecord::query()->where('repair_job_id', $jobId)->count());

        // The failed check sent it back to diagnosis; it goes through the flow again and passes.
        RepairJobRecord::query()->whereKey($jobId)->update(['repair_status' => 'qc_pending']);
        app(RecordQualityCheckHandler::class)->handle(new RecordQualityCheckCommand($jobId, 'passed', null, $this->staff->id));

        $this->assertSame(2, RepairQcCheckRecord::query()->where('repair_job_id', $jobId)->count());
    }

    public function test_an_open_repair_can_be_cancelled_with_a_reason(): void
    {
        $jobId = $this->job('awaiting_authorization');

        app(CancelRepairHandler::class)->handle(new CancelRepairCommand($jobId, 'Customer declined the quote', $this->staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'cancelled', 'resolution_notes' => 'Customer declined the quote']);
    }

    public function test_failing_a_repair_records_the_service_charge_and_refund_due(): void
    {
        $jobId = $this->job('in_progress');
        PaymentTransactionRecord::factory()->create([
            'payable_type' => 'repair_job',
            'payable_id' => $jobId,
            'purpose' => 'repair_down_payment',
            'amount_minor' => 3000000,
            'status' => 'confirmed',
        ]);

        app(FailRepairHandler::class)->handle(new FailRepairCommand($jobId, 'Still faulty', $this->staff->id, 1000000));

        $this->assertDatabaseHas('repair_jobs', [
            'id' => $jobId,
            'repair_status' => 'failed_requires_resolution',
            'failure_service_charge_minor' => 1000000,
            'failure_refund_due_minor' => 2000000,
        ]);
    }

    public function test_a_completed_repair_cannot_be_cancelled(): void
    {
        $jobId = $this->job('completed');

        $this->expectException(InvalidRepairTransition::class);

        app(CancelRepairHandler::class)->handle(new CancelRepairCommand($jobId, 'Too late', $this->staff->id));
    }

    public function test_an_authorized_quote_with_no_down_payment_starts_the_repair_straight_away(): void
    {
        $jobId = $this->job('awaiting_authorization', repairCostMinor: 1500000);

        app(AuthorizeRepairHandler::class)->handle(new AuthorizeRepairCommand($jobId, 0, $this->staff->id, 1500000));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'in_progress']);
    }

    public function test_a_diagnosis_fee_can_be_collected_after_frontdesk_sets_it(): void
    {
        $jobId = $this->job('diagnosing');
        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $this->staff->id));

        app(RequestDiagnosisFeeHandler::class)->handle(new RequestDiagnosisFeeCommand($jobId, 'cash', $this->staff->id));

        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $jobId,
            'purpose' => 'repair_diagnosis_fee',
            'amount_minor' => 500000,
        ]);
    }

    public function test_an_approved_diagnosis_fee_is_collected_as_its_own_payment_and_leaves_the_repair_status_alone(): void
    {
        $jobId = $this->job('diagnosing');
        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $this->staff->id));

        app(RequestDiagnosisFeeHandler::class)->handle(new RequestDiagnosisFeeCommand($jobId, 'bank_transfer', $this->staff->id));

        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $jobId,
            'purpose' => 'repair_diagnosis_fee',
            'amount_minor' => 500000,
        ]);
        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'diagnosing']);
    }

    public function test_a_declined_diagnosis_fee_is_not_due_and_cannot_be_collected(): void
    {
        $jobId = $this->job('diagnosing');
        RepairDiagnosisFeeRecord::query()->create([
            'repair_job_id' => $jobId,
            'amount_minor' => 500000,
            'decision' => 'declined',
            'set_by_staff_id' => $this->staff->id,
            'decided_by_staff_id' => $this->staff->id,
            'decided_at' => now(),
        ]);

        $this->expectException(InvalidDiagnosisFeeState::class);

        app(RequestDiagnosisFeeHandler::class)->handle(new RequestDiagnosisFeeCommand($jobId, 'cash', $this->staff->id));
    }

    public function test_a_diagnosis_fee_cannot_be_set_once_the_repair_is_in_progress(): void
    {
        $jobId = $this->job('in_progress');

        $this->expectException(InvalidRepairTransition::class);

        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $this->staff->id));
    }

    public function test_an_approved_fee_is_still_collectable_after_the_customer_declines_the_repair(): void
    {
        $jobId = $this->job('diagnosing');
        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $this->staff->id));
        app(CancelRepairHandler::class)->handle(new CancelRepairCommand($jobId, 'Quote declined', $this->staff->id));

        app(RequestDiagnosisFeeHandler::class)->handle(new RequestDiagnosisFeeCommand($jobId, 'cash', $this->staff->id));

        $this->assertDatabaseHas('payments_transactions', ['payable_id' => $jobId, 'purpose' => 'repair_diagnosis_fee', 'status' => 'confirmed']);
    }

    private function job(string $status, ?int $repairCostMinor = null): int
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        return RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'repair_status' => $status,
            'repair_cost_minor' => $repairCostMinor,
        ])->id;
    }
}
