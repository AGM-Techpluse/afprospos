<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

final readonly class MarkBankTransferPendingCommand
{
    public function __construct(
        public int $transactionId,
        public ?string $providerReference = null,
    ) {}
}
