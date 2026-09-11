<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class InvoiceNumber
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('InvoiceNumber cannot be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
