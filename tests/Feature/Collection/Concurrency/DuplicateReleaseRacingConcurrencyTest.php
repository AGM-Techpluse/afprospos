<?php

declare(strict_types=1);

namespace Tests\Feature\Collection\Concurrency;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Symfony\Component\Process\Process;
use Tests\Support\UsesConcurrencyDatabase;
use Tests\TestCase;

/**
 * Two real OS processes race ReleaseRepairDeviceCommand against the same
 * collection case — the SELECT ... FOR UPDATE on collection_cases is
 * what makes exactly one succeed, direct analogue of
 * DuplicateConfirmRacingConcurrencyTest (Payments) except Collection's
 * resolve() has no idempotent-duplicate guard, so the loser gets a hard
 * InvalidCollectionTransition, same shape as Sales' expiry-vs-payment race.
 */
class DuplicateReleaseRacingConcurrencyTest extends TestCase
{
    use UsesConcurrencyDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConcurrencyDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownConcurrencyDatabase(['collection_case_events', 'collection_cases', 'repair_jobs', 'staff', 'shops', 'customers']);
        parent::tearDown();
    }

    public function test_only_one_of_two_concurrent_releases_succeeds(): void
    {
        $staffA = StaffRecord::factory()->create();
        $staffB = StaffRecord::factory()->create();

        $job = RepairJobRecord::factory()->create(['repair_status' => 'completed', 'financial_status' => 'fully_paid']);
        $case = CollectionCaseRecord::factory()->create(['source_type' => 'repair_job', 'source_id' => $job->id]);

        $processA = $this->buildProcess($case->id, $staffA->id, holdMs: 400);
        $processB = $this->buildProcess($case->id, $staffB->id, holdMs: 0);

        $processA->start();
        usleep(50_000);
        $processB->start();

        $processA->wait();
        $processB->wait();

        $resultA = json_decode($processA->getOutput(), true);
        $resultB = json_decode($processB->getOutput(), true);

        $successCount = (int) ($resultA['success'] ?? false) + (int) ($resultB['success'] ?? false);

        $this->assertSame(
            1,
            $successCount,
            "Expected exactly one release to succeed.\n".
            'A: '.$processA->getOutput().' '.$processA->getErrorOutput()."\n".
            'B: '.$processB->getOutput().' '.$processB->getErrorOutput(),
        );

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'status' => 'resolved']);
    }

    private function buildProcess(int $caseId, int $staffId, int $holdMs): Process
    {
        return new Process([
            PHP_BINARY,
            __DIR__.'/Support/attempt_release_device.php',
            "--case={$caseId}",
            "--staff={$staffId}",
            "--hold-ms={$holdMs}",
        ], timeout: 15);
    }
}
