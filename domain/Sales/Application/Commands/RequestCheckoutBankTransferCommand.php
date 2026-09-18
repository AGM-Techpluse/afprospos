<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

/**
 * A customer telling us they've sent a bank transfer for their own open
 * checkout — this only creates a `payment_pending_confirmation`
 * PaymentTransaction; it never marks the checkout paid or creates the
 * Sale (that stays staff-confirmed, per ADD §14.2: "never trust a
 * client-side success screen").
 */
final readonly class RequestCheckoutBankTransferCommand
{
    public function __construct(
        public int $checkoutId,
        public int $customerId,
        public ?string $transferReference,
    ) {}
}
