<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\DTOs\PaymentInitiationResult;

/**
 * The published cross-module contract (Phase 5): Sales, and later Repair,
 * depend on this, never on Payments' Domain/Infrastructure internals or
 * Eloquent models directly (CPNC §4.2) — mirrors
 * `InventoryReservationService`'s role exactly.
 */
interface PaymentInitiationService
{
    public function initiate(InitiatePaymentCommand $command): PaymentInitiationResult;
}
