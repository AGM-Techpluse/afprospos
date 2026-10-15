<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\DTOs;

/** One parsed, not-yet-validated CSV row (BRD INV-01). Fixed columns — see the Import page's documented template. */
final readonly class ProductImportRow
{
    public function __construct(
        public int $rowNumber,
        public string $brand,
        public string $model,
        public string $category,
        public string $condition,
        public int $costPriceMinor,
        public float $markupPercent,
        public ?int $quantity,
        public ?string $imei,
        public ?int $lowStockThreshold,
        /** @var array<string, mixed> the SKU's configuration (color, material, compatibility, custom) — same shape as the single-product form */
        public array $attributes = [],
        public string $unit = 'pcs',
        public string $purpose = 'sale',
        public ?string $supplierName = null,
    ) {}
}
