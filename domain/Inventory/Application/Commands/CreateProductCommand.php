<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/**
 * BRD INV-02/INV-03/INV-08/INV-09: one admin action ("add this
 * device, with this much stock") that finds-or-creates the Product,
 * creates its first Sku, and receives the initial stock — matches how
 * a shop admin actually thinks, not the two-table catalog model
 * underneath (BLD §9.1).
 */
final readonly class CreateProductCommand
{
    /** @param  array<string, mixed>  $attributes @param  string[]|null  $initialImeis */
    public function __construct(
        public string $brand,
        public string $model,
        public string $category,
        public array $attributes,
        public bool $isSerialized,
        public int $costPriceMinor,
        public float $markupPercent,
        public ?int $sellingPriceMinorOverride,
        public ?int $lowStockThreshold,
        public string $shopCode,
        public int $shopId,
        public string $condition,
        public ?int $initialQuantity,
        public ?array $initialImeis,
        public int $createdByStaffId,
    ) {}
}
