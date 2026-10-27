<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class UpdateSkuAttributesCommand
{
    /** @param  array<string, mixed>  $attributes */
    public function __construct(
        public int $skuId,
        public array $attributes,
        public int $updatedByStaffId,
    ) {}
}
