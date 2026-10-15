<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** Toggles commission on an already-existing Sku — the realistic admin workflow, since commissionability is usually decided after a model is already in the catalog. */
final readonly class UpdateSkuCommissionCommand
{
    public function __construct(
        public int $skuId,
        public bool $isCommissionable,
        public ?int $commissionAmountMinor,
        public int $updatedByStaffId,
    ) {}
}
