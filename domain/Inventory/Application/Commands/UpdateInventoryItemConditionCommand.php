<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class UpdateInventoryItemConditionCommand
{
    public function __construct(
        public int $inventoryItemId,
        public string $condition,
        public int $updatedByStaffId,
    ) {}
}
