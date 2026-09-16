<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnrepairableDeviceCreatesReturnCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosing_a_device_unrepairable_creates_a_ready_for_return_collection_case(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $jobId = app(CreateRepairJobHandler::class)->handle(new CreateRepairJobCommand(
            shopId: $shop->id,
            customerId: $customer->id,
            deviceMake: 'Samsung',
            deviceModel: 'Galaxy S9',
            reportedIssue: 'Water damage, will not power on',
            deviceImeiSerial: '356938035601002',
            deviceLockType: 'none',
            deviceLockValue: null,
            problemTagIds: [],
            labourChargeMinor: 0,
            createdByStaffId: $staff->id,
        ));

        app(RecordDiagnosisHandler::class)->handle(new RecordDiagnosisCommand(
            repairJobId: $jobId,
            component: 'motherboard',
            condition: 'faulty',
            notes: 'Liquid damage beyond repair',
            outcome: 'unrepairable',
            diagnosedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_jobs', [
            'id' => $jobId,
            'repair_status' => 'unrepairable',
            'unrepairable_settlement_state' => 'pending_decision',
        ]);

        $this->assertDatabaseHas('collection_cases', [
            'source_type' => 'repair_job',
            'source_id' => $jobId,
            'context' => 'ready_for_return',
            'status' => 'pending',
        ]);

        $this->assertSame(1, CollectionCaseRecord::query()->where('source_id', $jobId)->count());
    }
}
