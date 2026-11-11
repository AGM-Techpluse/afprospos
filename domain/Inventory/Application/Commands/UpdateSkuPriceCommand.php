<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** Correcting a Sku's price after creation — a wrong cost price, markup, or override entered at intake. */
final readonly class UpdateSkuPriceCommand
{
    public function __construct(
        public int $skuId,
        public int $costPriceMinor,
        public float $markupPercent,
        /** Null clears any existing manual override and reverts to the auto-calculated price. */
        public ?int $sellingPriceMinorOverride,
        public int $updatedByStaffId,
    ) {}
}
