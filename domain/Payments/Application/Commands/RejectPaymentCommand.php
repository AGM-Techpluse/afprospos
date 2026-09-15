<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class RejectPaymentCommand
{
    public function __construct(
        public int $transactionId,
        public int $rejectedByStaffId,
    ) {}
}
