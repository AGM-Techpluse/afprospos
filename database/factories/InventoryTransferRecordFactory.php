<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryTransferRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryTransferRecord> */
final class InventoryTransferRecordFactory extends Factory
{
    protected $model = InventoryTransferRecord::class;

    public function definition(): array
    {
        return [
            'sku_id' => SkuRecord::factory(),
            'inventory_item_id' => null,
            'quantity' => 5,
            'from_shop_id' => ShopRecord::factory(),
            'to_shop_id' => ShopRecord::factory(),
            'initiated_by_staff_id' => StaffRecord::factory(),
            'received_by_staff_id' => null,
            'status' => 'in_transit',
        ];
    }
}
