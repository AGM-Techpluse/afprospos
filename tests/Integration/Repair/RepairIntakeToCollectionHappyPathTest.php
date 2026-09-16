<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Collection\Application\Commands\ReleaseRepairDeviceCommand;
use Domain\Collection\Application\Handlers\ReleaseRepairDeviceHandler;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Handlers\ConfirmPaymentHandler;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Commands\ConfirmDownPaymentCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Commands\StartRepairCommand;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Application\Handlers\ConfirmDownPaymentHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\InstallRepairPartHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Application\Handlers\StartRepairHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> real repositories -> real DB, no HTTP (CPNC §6's Integration tier). Exercises the full intake -> collection lifecycle in one pass. */
class RepairIntakeToCollectionHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_repairable_device_flows_from_intake_through_release(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $jobId = app(CreateRepairJobHandler::class)->handle(new CreateRepairJobCommand(
            shopId: $shop->id,
            customerId: $customer->id,
            deviceMake: 'Apple',
            deviceModel: 'iPhone 12',
            reportedIssue: 'Screen cracked after a drop',
            deviceImeiSerial: '356938035601001',
            deviceLockType: 'code',
            deviceLockValue: '1234',
            problemTagIds: [],
            labourChargeMinor: 500000,
            createdByStaffId: $staff->id,
        ));

        app(RecordDiagnosisHandler::class)->handle(new RecordDiagnosisCommand(
            repairJobId: $jobId,
            component: 'screen',
            condition: 'faulty',
            notes: 'Cracked glass',
            outcome: 'repairable',
            diagnosedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'awaiting_authorization']);
        $this->assertSame('code', RepairJobRecord::query()->findOrFail($jobId)->device_lock_type);
        $this->assertSame('1234', RepairJobRecord::query()->findOrFail($jobId)->device_lock_value); // decrypted transparently via the model's `encrypted` cast

        app(AuthorizeRepairHandler::class)->handle(new AuthorizeRepairCommand(
            repairJobId: $jobId,
            downPaymentRequiredMinor: 200000,
            downPaymentMethod: 'cash',
            authorizedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'awaiting_authorization']);
        $transaction = PaymentTransactionRecord::query()
            ->where('payable_type', 'repair_job')
            ->where('payable_id', $jobId)
            ->firstOrFail();

        app(ConfirmPaymentHandler::class)->handle(new ConfirmPaymentCommand(
            transactionId: $transaction->id,
            confirmedByStaffId: $staff->id,
        ));
        app(ConfirmDownPaymentHandler::class)->handle(new ConfirmDownPaymentCommand($jobId, $staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'awaiting_parts']);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $jobId,
            skuId: $sku->id,
            quantity: 1,
            reservedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_parts_reservations', ['repair_job_id' => $jobId, 'status' => 'reserved']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 1]);

        $reservationId = RepairPartReservationRecord::query()
            ->where('repair_job_id', $jobId)->firstOrFail()->id;

        app(InstallRepairPartHandler::class)->handle(new InstallRepairPartCommand($reservationId));

        $this->assertDatabaseHas('repair_parts_reservations', ['id' => $reservationId, 'status' => 'installed']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 4, 'reserved' => 0]);

        app(StartRepairHandler::class)->handle(new StartRepairCommand($jobId, $staff->id));
        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'in_progress']);

        app(CompleteRepairHandler::class)->handle(new CompleteRepairCommand($jobId, 'fully_paid', 'Replaced screen assembly', $staff->id));
        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'completed', 'financial_status' => 'fully_paid']);

        $case = CollectionCaseRecord::query()->where('source_type', 'repair_job')->where('source_id', $jobId)->firstOrFail();
        $this->assertSame('ready_for_collection', $case->context);
        $this->assertSame('pending', $case->status);

        app(ReleaseRepairDeviceHandler::class)->handle(new ReleaseRepairDeviceCommand($case->id, $staff->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'status' => 'resolved']);
        $this->assertSame(1, RepairJobRecord::query()->count());

        $releasedJob = RepairJobRecord::query()->findOrFail($jobId);
        $this->assertSame('none', $releasedJob->device_lock_type);
        $this->assertNull($releasedJob->device_lock_value);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceLockCleared', 'subject_id' => $jobId]);
    }
}
