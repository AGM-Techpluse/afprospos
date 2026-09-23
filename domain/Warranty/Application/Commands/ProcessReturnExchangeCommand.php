<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/** WAR-BR-13: resolves an approved Return as an Exchange — the sale's full paid value becomes a store-credit adjustment on the given new checkout, instead of a cash refund. */
final readonly class ProcessReturnExchangeCommand
{
    public function __construct(
        public int $returnRequestId,
        public int $newCheckoutId,
        public int $processedByStaffId,
    ) {}
}
