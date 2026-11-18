<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class UpdateProductMetadataCommand
{
    public function __construct(
        public int $productId,
        public string $brand,
        public string $model,
        public string $category,
        public string $unit,
        public int $updatedByStaffId,
    ) {}
}
