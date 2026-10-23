<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class UpdateProductDescriptionCommand
{
    public function __construct(
        public int $productId,
        public ?string $description,
        public int $updatedByStaffId,
    ) {}
}
