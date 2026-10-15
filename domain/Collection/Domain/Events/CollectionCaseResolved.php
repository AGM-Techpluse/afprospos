<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class CollectionCaseResolved
{
    public function __construct(
        public int $collectionCaseId,
        public CarbonImmutable $occurredAt,
        /** The record the device belongs to, e.g. 'repair_job' and its id, so the owning module can react to the release. */
        public ?string $sourceType = null,
        public ?int $sourceId = null,
    ) {}
}
