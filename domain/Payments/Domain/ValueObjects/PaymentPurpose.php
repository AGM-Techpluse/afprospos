<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\ValueObjects;

use InvalidArgumentException;

/** Finer-grained than `PayableType` alone — a `repair_job` payable is either its intake down payment or its completion settlement, and only `purpose` tells them apart. */
final readonly class PaymentPurpose
{
    private const VALID = ['sale_payment', 'repair_down_payment', 'repair_final_payment'];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid payment purpose [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
