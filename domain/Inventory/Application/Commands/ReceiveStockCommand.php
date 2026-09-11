<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/**
 * BRD INV-11 (stock-in). One business action, one Command — the
 * Handler branches on sku.isSerialized() internally (a normal domain
 * conditional, not the "generic handler accepting multiple Command
 * types" CPNC §2.4 forbids).
 */
final readonly class ReceiveStockCommand
{
    /** @param  string[]|null  $imeis */
    public function __construct(
        public int $skuId,
        public int $shopId,
        public string $condition,
        public ?int $quantity,
        public ?array $imeis,
        public int $receivedByStaffId,
    ) {}
}
