<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\DTOs;

/** One row of a multi-variant product submission (INV-BR-17) — always non-serialized (INV-BR-16). */
final readonly class VariantInput
{
    /** @param  array<string, mixed>  $attributes */
    public function __construct(
        public array $attributes,
        public int $costPriceMinor,
        public float $markupPercent,
        public ?int $sellingPriceMinorOverride,
        public ?int $lowStockThreshold,
        public ?int $initialQuantity,
    ) {}
}
