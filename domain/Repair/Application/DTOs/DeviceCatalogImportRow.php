<?php

declare(strict_types=1);

namespace Domain\Repair\Application\DTOs;

/**
 * One parsed, not-yet-validated CSV row. deviceType/icon/brand are required
 * every row; model and fault are each independently optional — a row can
 * seed just the type/brand, go all the way to a model, or also attach a
 * fault (model-specific if a model was given on the same row, else a
 * type-level default).
 */
final readonly class DeviceCatalogImportRow
{
    public function __construct(
        public int $rowNumber,
        public string $deviceTypeLabel,
        public string $icon,
        public string $brandName,
        public ?string $modelName,
        public ?string $faultLabel,
        public ?int $commissionAmountMinor,
    ) {}
}
