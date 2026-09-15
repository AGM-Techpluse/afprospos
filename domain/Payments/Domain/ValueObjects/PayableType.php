<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\ValueObjects;

use InvalidArgumentException;

/** The four business objects a payment can belong to (DBDD §16.1) — a cross-module string tag, never a foreign key (Payments is a standalone bounded context). */
final readonly class PayableType
{
    private const VALID = ['sales_checkout', 'repair_job', 'warranty_claim', 'return_request'];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid payable type [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
