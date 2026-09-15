<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class PaymentTransactionId
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('PaymentTransactionId must be a positive integer.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
