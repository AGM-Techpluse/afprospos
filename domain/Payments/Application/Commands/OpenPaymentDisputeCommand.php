<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class OpenPaymentDisputeCommand
{
    public function __construct(
        public int $transactionId,
        public int $openedByStaffId,
        public ?string $disputeProofReference = null,
    ) {}
}
