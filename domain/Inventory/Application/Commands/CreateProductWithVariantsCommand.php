<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

use Domain\Inventory\Application\DTOs\VariantInput;

/** INV-BR-17: creates one Product with several non-serialized Sku variants in a single submission — e.g. a screenguard product with one Sku per brand. */
final readonly class CreateProductWithVariantsCommand
{
    /** @param  VariantInput[]  $variants */
    public function __construct(
        public string $brand,
        public string $model,
        public string $category,
        public string $shopCode,
        public int $shopId,
        public string $condition,
        public array $variants,
        public int $createdByStaffId,
    ) {}
}
