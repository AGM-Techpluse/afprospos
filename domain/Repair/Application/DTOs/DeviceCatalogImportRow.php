<?php

declare(strict_types=1);

namespace Domain\Repair\Application\DTOs;

/**
 * One parsed, not-yet-validated CSV row. deviceType/icon/brand are required
 * every row; model and fault are each independently optional — a row can
 * seed just the type/brand, go all the way to a model, or also attach a
 * fault (model-specific if a model was given on the same row, else a
 * type-level default). `suggestedSkuCodes` only matters when `faultLabel`
 * is also given — each code is resolved to a SKU and attached as a
 * suggested repair part for that fault; an unknown code is a soft
 * per-row warning, not a failure.
 */
final readonly class DeviceCatalogImportRow
{
    /** @param  string[]|null  $suggestedSkuCodes */
    public function __construct(
        public int $rowNumber,
        public string $deviceTypeLabel,
        public string $icon,
        public string $brandName,
        public ?string $modelName,
        public ?string $faultLabel,
        public ?int $commissionAmountMinor,
        public ?array $suggestedSkuCodes = null,
    ) {}
}
