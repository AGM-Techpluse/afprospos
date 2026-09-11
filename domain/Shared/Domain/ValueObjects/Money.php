<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Money is always stored/passed as an integer of minor units (kobo),
 * per DBDD §2.3. Never introduce a DECIMAL/FLOAT money field or a
 * bare `$amount` variable anywhere money crosses a layer boundary —
 * CPNC §3.5 requires the unit to be explicit ($amountMinor).
 */
final readonly class Money
{
    public function __construct(public int $minor)
    {
        if ($minor < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->minor + $other->minor);
    }

    public function subtract(self $other): self
    {
        if ($other->minor > $this->minor) {
            throw new InvalidArgumentException('Cannot subtract more money than is present.');
        }

        return new self($this->minor - $other->minor);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function equals(self $other): bool
    {
        return $this->minor === $other->minor;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->minor > $other->minor;
    }

    /**
     * Cost + (cost * percent / 100), rounded to the nearest minor unit
     * (DBDD §14.2 `markup_percent` -> `selling_price_minor`). Half-up
     * rounding, since a price is never allowed to under-recover cost.
     */
    public function withMarkupPercent(float $percent): self
    {
        return new self($this->minor + (int) round($this->minor * $percent / 100));
    }
}
