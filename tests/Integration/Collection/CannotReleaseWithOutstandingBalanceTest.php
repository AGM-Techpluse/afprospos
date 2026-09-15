<?php

declare(strict_types=1);

namespace Tests\Integration\Collection;

use Domain\Collection\Application\Commands\RecordCollectionOverrideCommand;
use Domain\Collection\Application\Commands\ReleaseRepairDeviceCommand;
use Domain\Collection\Application\Handlers\RecordCollectionOverrideHandler;
use Domain\Collection\Application\Handlers\ReleaseRepairDeviceHandler;
use Domain\Collection\Domain\Exceptions\ReleaseBlockedByOutstandingBalance;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Implementation Plan Phase 6 exit criterion: "the device cannot be released when payment is outstanding without an authorized, audited override." */
class CannotReleaseWithOutstandingBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_is_blocked_while_the_repair_job_is_not_fully_paid(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['repair_status' => 'completed', 'financial_status' => 'partially_paid']);
        $case = CollectionCaseRecord::factory()->create(['source_type' => 'repair_job', 'source_id' => $job->id]);

        $this->expectException(ReleaseBlockedByOutstandingBalance::class);

        app(ReleaseRepairDeviceHandler::class)->handle(new ReleaseRepairDeviceCommand($case->id, $staff->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'status' => 'pending']);
    }

    public function test_release_succeeds_once_an_administrative_override_is_recorded(): void
    {
        $staff = StaffRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['repair_status' => 'completed', 'financial_status' => 'partially_paid']);
        $case = CollectionCaseRecord::factory()->create(['source_type' => 'repair_job', 'source_id' => $job->id]);

        app(RecordCollectionOverrideHandler::class)->handle(new RecordCollectionOverrideCommand(
            collectionCaseId: $case->id,
            overriddenByStaffId: $staff->id,
            reason: 'Customer is a longtime client, goodwill release.',
        ));

        app(ReleaseRepairDeviceHandler::class)->handle(new ReleaseRepairDeviceCommand($case->id, $staff->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'status' => 'resolved']);
        $this->assertDatabaseHas('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'administrative_resolution']);
    }
}
