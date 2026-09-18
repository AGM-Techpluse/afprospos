<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use Domain\Payments\Application\Commands\ConfirmPaymentCommand;

/**
 * Published cross-module contract, mirrors `PaymentRefundService`'s exact
 * one-method shape: lets a caller (Sales, confirming a customer-submitted
 * bank transfer at sale-completion time) confirm an existing transaction
 * instead of only ever being able to initiate a new one.
 */
interface PaymentConfirmationService
{
    public function confirm(ConfirmPaymentCommand $command): void;
}
