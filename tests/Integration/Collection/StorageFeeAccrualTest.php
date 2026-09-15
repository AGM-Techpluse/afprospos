<?php

declare(strict_types=1);

namespace Tests\Integration\Collection;

use Domain\Collection\Application\Commands\AccrueStorageFeeCommand;
use Domain\Collection\Application\Handlers\AccrueStorageFeeHandler;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One flat daily rate, snapshotted at case-creation time — no proration, no tiers (Phase 6 scope decision). */
class StorageFeeAccrualTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_run_accrues_exactly_the_snapshotted_daily_rate(): void
    {
        $case = CollectionCaseRecord::factory()->create([
            'storage_fee_policy_snapshot' => ['minor_per_day' => 500, 'grace_days' => 0],
            'accrued_storage_fee_minor' => 0,
        ]);

        app(AccrueStorageFeeHandler::class)->handle(new AccrueStorageFeeCommand($case->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'accrued_storage_fee_minor' => 500]);
        $this->assertDatabaseHas('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'fee_accrued']);
    }

    public function test_a_later_config_change_does_not_retroactively_alter_an_already_open_cases_snapshot(): void
    {
        $case = CollectionCaseRecord::factory()->create([
            'storage_fee_policy_snapshot' => ['minor_per_day' => 300, 'grace_days' => 0],
            'accrued_storage_fee_minor' => 900,
        ]);

        config(['afprospos.collection.storage_fee_minor_per_day' => 999999]);

        app(AccrueStorageFeeHandler::class)->handle(new AccrueStorageFeeCommand($case->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'accrued_storage_fee_minor' => 1200]);
    }

    public function test_a_zero_rate_snapshot_accrues_nothing(): void
    {
        $case = CollectionCaseRecord::factory()->create([
            'storage_fee_policy_snapshot' => ['minor_per_day' => 0, 'grace_days' => 0],
            'accrued_storage_fee_minor' => 0,
        ]);

        app(AccrueStorageFeeHandler::class)->handle(new AccrueStorageFeeCommand($case->id));

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'accrued_storage_fee_minor' => 0]);
        $this->assertDatabaseMissing('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'fee_accrued']);
    }
}
