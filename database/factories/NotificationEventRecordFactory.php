<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Notifications\Infrastructure\Persistence\Eloquent\NotificationEventRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationEventRecord> */
final class NotificationEventRecordFactory extends Factory
{
    protected $model = NotificationEventRecord::class;

    public function definition(): array
    {
        return [
            'event_type' => 'RepairCompleted',
            'source_module' => 'Repair',
            'source_id' => $this->faker->numberBetween(1, 1000),
            'recipient_type' => 'customer',
            'recipient_id' => $this->faker->numberBetween(1, 1000),
            'category' => 'transactional',
            'payload' => [],
            'status' => 'queued',
            'read_at' => null,
        ];
    }

    public function delivered(): self
    {
        return $this->state(fn (): array => ['status' => 'delivered']);
    }

    public function failedExhausted(): self
    {
        return $this->state(fn (): array => ['status' => 'failed_exhausted']);
    }
}
