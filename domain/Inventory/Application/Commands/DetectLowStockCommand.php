<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class DetectLowStockCommand
{
    public function __construct(
        public ?int $shopId = null,
    ) {}
}
