<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Commission\Infrastructure\Persistence\Eloquent\CommissionLedgerEntryRecord;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteRepairCreditsSnapshottedCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_edited_commission_amount_is_credited_and_snapshotted_not_the_catalog_default(): void
    {
        $technician = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['technician_staff_id' => $technician->id, 'repair_status' => 'in_progress']);
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $tag = DeviceProblemTagRecord::query()->create(['device_type_id' => $type->id, 'device_model_id' => null, 'label' => 'Screen', 'sort_order' => 0, 'commission_amount_minor' => 500000]);

        app(CompleteRepairHandler::class)->handle(new CompleteRepairCommand(
            repairJobId: $job->id,
            resolutionNotes: null,
            fixedFaults: [[
                'device_problem_tag_id' => $tag->id,
                'label' => 'Screen',
                'commission_amount_minor' => 350000, // edited down from the catalog's 500000
            ]],
            completedByStaffId: $technician->id,
        ));

        $this->assertDatabaseHas('repair_fixed_faults', [
            'repair_job_id' => $job->id,
            'device_problem_tag_id' => $tag->id,
            'commission_amount_minor' => 350000,
        ]);
        $this->assertDatabaseHas('commission_ledger_entries', [
            'staff_id' => $technician->id,
            'source_type' => 'repair',
            'source_id' => $job->id,
            'commissionable_amount_minor' => 350000,
        ]);

        // Changing the catalog's commission afterward must not retroactively change what this already-completed repair credited.
        $tag->update(['commission_amount_minor' => 999999]);
        $this->assertDatabaseHas('repair_fixed_faults', ['repair_job_id' => $job->id, 'commission_amount_minor' => 350000]);
    }

    public function test_a_fault_submitted_without_a_commission_amount_falls_back_to_the_catalog_default(): void
    {
        $technician = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['technician_staff_id' => $technician->id, 'repair_status' => 'in_progress']);
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $tag = DeviceProblemTagRecord::query()->create(['device_type_id' => $type->id, 'device_model_id' => null, 'label' => 'Battery', 'sort_order' => 0, 'commission_amount_minor' => 200000]);

        app(CompleteRepairHandler::class)->handle(new CompleteRepairCommand(
            repairJobId: $job->id,
            resolutionNotes: null,
            fixedFaults: [[
                'device_problem_tag_id' => $tag->id,
                'label' => 'Battery',
            ]],
            completedByStaffId: $technician->id,
        ));

        $this->assertDatabaseHas('repair_fixed_faults', ['repair_job_id' => $job->id, 'commission_amount_minor' => 200000]);
        $this->assertSame(1, CommissionLedgerEntryRecord::query()->where('source_id', $job->id)->count());
    }
}
