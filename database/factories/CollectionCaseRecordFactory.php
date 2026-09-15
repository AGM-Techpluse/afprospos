<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CollectionCaseRecord> */
final class CollectionCaseRecordFactory extends Factory
{
    protected $model = CollectionCaseRecord::class;

    public function definition(): array
    {
        $shopId = ShopRecord::factory()->create()->id;

        return [
            'source_type' => 'repair_job',
            'source_id' => 1,
            'shop_id' => $shopId,
            'originating_shop_id' => $shopId,
            'context' => 'ready_for_collection',
            'status' => 'pending',
            'collection_deadline_at' => now()->addDays(14),
            'abandonment_threshold_at' => now()->addDays(44),
            'storage_fee_policy_snapshot' => ['minor_per_day' => 0, 'grace_days' => 3],
            'accrued_storage_fee_minor' => 0,
            'shop_override_reason' => null,
        ];
    }

    public function overdue(): self
    {
        return $this->state(fn (): array => ['status' => 'overdue', 'collection_deadline_at' => now()->subDay()]);
    }
}
