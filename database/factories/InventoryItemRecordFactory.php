<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryItemRecord> */
final class InventoryItemRecordFactory extends Factory
{
    protected $model = InventoryItemRecord::class;

    public function definition(): array
    {
        return [
            'sku_id' => SkuRecord::factory(),
            'imei' => fake()->unique()->numerify('###############'),
            'current_shop_id' => ShopRecord::factory(),
            'condition' => 'new',
            'status' => 'available',
            'reserved_by_type' => null,
            'reserved_by_id' => null,
            'version' => 0,
        ];
    }

    public function reserved(string $sourceType = 'checkout', int $sourceId = 1): self
    {
        return $this->state(fn (): array => [
            'status' => 'reserved',
            'reserved_by_type' => $sourceType,
            'reserved_by_id' => $sourceId,
        ]);
    }
}
