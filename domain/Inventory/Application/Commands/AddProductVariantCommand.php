<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** INV-BR-18: adds a new non-serialized Sku variant to an already-existing Product. */
final readonly class AddProductVariantCommand
{
    /** @param  array<string, mixed>  $attributes */
    public function __construct(
        public int $productId,
        public array $attributes,
        public int $costPriceMinor,
        public float $markupPercent,
        public ?int $sellingPriceMinorOverride,
        public ?int $lowStockThreshold,
        public string $shopCode,
        public int $shopId,
        public string $condition,
        public ?int $initialQuantity,
        public int $createdByStaffId,
    ) {}
}
