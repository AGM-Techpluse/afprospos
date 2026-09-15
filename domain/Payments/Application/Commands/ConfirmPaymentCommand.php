<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class ConfirmPaymentCommand
{
    public function __construct(
        public int $transactionId,
        public int $confirmedByStaffId,
        public ?string $providerReference = null,
    ) {}
}
