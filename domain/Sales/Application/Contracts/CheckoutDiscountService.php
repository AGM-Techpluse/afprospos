<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Contracts;

use Domain\Sales\Application\Commands\ApplyDiscountCommand;

/**
 * The published cross-module write contract for applying a checkout
 * adjustment (mirrors SaleLookup's role as Sales' other published
 * contract) — Warranty's Trade-in approval depends on this, never on
 * Sales' Application\Handlers directly (CPNC §4.2), to apply an
 * approved trade-in value as a `trade_in_credit` adjustment (TRADE-BR-02).
 *
 * Returns `false` (rather than letting Sales' `CheckoutNotOpen` cross
 * the module boundary — CPNC §4.2 forbids Warranty depending on Sales'
 * Domain\Exceptions) when the checkout can no longer accept the
 * adjustment, so the caller can fall back gracefully instead of failing.
 */
interface CheckoutDiscountService
{
    public function apply(ApplyDiscountCommand $command): bool;
}
