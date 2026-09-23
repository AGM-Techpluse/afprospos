<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Events;

use Carbon\CarbonImmutable;

/** MKT-BR-15: a discount was applied and now needs Shop Owner approval before the checkout can be paid. */
final readonly class CheckoutDiscountApplied
{
    public function __construct(
        public int $checkoutId,
        public int $shopId,
        public int $amountMinor,
        public int $appliedByStaffId,
        public CarbonImmutable $occurredAt,
    ) {}
}
