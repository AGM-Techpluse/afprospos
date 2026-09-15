<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class CollectionCaseCreated
{
    public function __construct(
        public int $collectionCaseId,
        public string $sourceType,
        public int $sourceId,
        public CarbonImmutable $occurredAt,
    ) {}
}
