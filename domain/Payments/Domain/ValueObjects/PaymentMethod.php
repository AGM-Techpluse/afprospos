<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\ValueObjects;

use InvalidArgumentException;

/** Mirrors `sales_sales.payment_method`'s enum exactly (DBDD §16.1) — `in_app` stays a legal value with no registered gateway (Phase 5 scope: no online provider yet). */
final readonly class PaymentMethod
{
    private const VALID = ['cash', 'pos_terminal', 'bank_transfer', 'in_app'];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid payment method [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
