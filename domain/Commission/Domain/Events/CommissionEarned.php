<?php

declare(strict_types=1);

namespace Domain\Commission\Domain\Events;

final readonly class CommissionEarned
{
    public function __construct(
        public int $entryId,
        public int $staffId,
        public string $sourceType,
        public int $sourceId,
        public int $amountMinor,
    ) {}
}
