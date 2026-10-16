<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class PaymentRefundExceedsRemainingBalance extends DomainException
{
    public static function forTransaction(int $transactionId, int $requestedMinor, int $remainingMinor): self
    {
        return new self("Payment transaction [{$transactionId}] cannot refund [{$requestedMinor}] — only [{$remainingMinor}] remains refundable.");
    }
}
