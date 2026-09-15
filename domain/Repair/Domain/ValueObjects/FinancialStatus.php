<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\ValueObjects;

use InvalidArgumentException;

/** Mirrors `repair_jobs.financial_status`'s enum exactly (DBDD §11.1) — independent of repair_status/device disposition. */
final readonly class FinancialStatus
{
    private const VALID = ['unpaid', 'partially_paid', 'fully_paid'];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid financial status [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
