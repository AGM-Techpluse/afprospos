<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class CollectionCaseResolved
{
    public function __construct(
        public int $collectionCaseId,
        public CarbonImmutable $occurredAt,
    ) {}
}
