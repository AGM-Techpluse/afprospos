<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryStockLevelRecord> */
final class InventoryStockLevelRecordFactory extends Factory
{
    protected $model = InventoryStockLevelRecord::class;

    public function definition(): array
    {
        return [
            'sku_id' => SkuRecord::factory()->nonSerialized(),
            'shop_id' => ShopRecord::factory(),
            'on_hand' => 10,
            'reserved' => 0,
            'version' => 0,
        ];
    }
}
