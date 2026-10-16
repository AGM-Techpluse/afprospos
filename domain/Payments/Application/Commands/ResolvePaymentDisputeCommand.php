<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class ResolvePaymentDisputeCommand
{
    /**
     * `amountMinor` is only read when resolution is 'refunded'; null there
     * means "refund whatever remains outstanding."
     *
     * @param  'confirmed'|'refunded'|'exception'  $resolution
     */
    public function __construct(
        public int $transactionId,
        public int $resolvedByStaffId,
        public string $resolution,
        public ?int $amountMinor = null,
    ) {}
}
