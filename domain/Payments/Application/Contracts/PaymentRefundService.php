<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use Domain\Payments\Application\Commands\RefundPaymentCommand;

/**
 * The published cross-module contract (Phase 7): Warranty depends on
 * this, never on Payments' Domain/Infrastructure internals or Eloquent
 * models directly (CPNC §4.2) — mirrors `PaymentInitiationService`'s
 * role exactly.
 */
interface PaymentRefundService
{
    public function refund(RefundPaymentCommand $command): void;
}
