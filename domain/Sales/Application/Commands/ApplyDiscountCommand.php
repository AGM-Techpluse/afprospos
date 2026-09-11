<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

/** MKT-BR-06: applying a new adjustment replaces any existing one on this checkout (non-stackable default). */
final readonly class ApplyDiscountCommand
{
    public function __construct(
        public int $checkoutId,
        public string $type,
        public int $sourceId,
        public int $amountMinor,
    ) {}
}
