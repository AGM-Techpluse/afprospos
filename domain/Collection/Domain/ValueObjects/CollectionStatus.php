<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\ValueObjects;

use InvalidArgumentException;

/** Mirrors `collection_cases.status`'s enum exactly (DBDD §12.1). */
final readonly class CollectionStatus
{
    private const VALID = ['pending', 'overdue', 'abandoned', 'resolved'];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid collection status [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
