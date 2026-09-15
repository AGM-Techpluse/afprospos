<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseEventRecord;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CollectionCaseEventRecord> */
final class CollectionCaseEventRecordFactory extends Factory
{
    protected $model = CollectionCaseEventRecord::class;

    public function definition(): array
    {
        return [
            'collection_case_id' => CollectionCaseRecord::factory(),
            'event_type' => 'deadline_set',
            'actor_staff_id' => null,
            'detail' => [],
        ];
    }
}
