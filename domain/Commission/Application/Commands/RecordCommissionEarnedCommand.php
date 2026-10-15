<?php

declare(strict_types=1);

namespace Domain\Commission\Application\Commands;

final readonly class RecordCommissionEarnedCommand
{
    public function __construct(
        public int $staffId,
        public string $sourceType,
        public int $sourceId,
        public int $amountMinor,
        public int $shopId,
    ) {}
}
