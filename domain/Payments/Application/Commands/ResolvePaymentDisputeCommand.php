<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class ResolvePaymentDisputeCommand
{
    /** @param  'confirmed'|'refunded'|'exception'  $resolution */
    public function __construct(
        public int $transactionId,
        public int $resolvedByStaffId,
        public string $resolution,
    ) {}
}
