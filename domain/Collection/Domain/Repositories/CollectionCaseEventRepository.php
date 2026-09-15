<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Repositories;

use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;

interface CollectionCaseEventRepository
{
    /** Append-only — an event is never updated. */
    public function save(CollectionCaseEvent $event): void;

    /** @return CollectionCaseEvent[] */
    public function findByCase(CollectionCaseId $collectionCaseId): array;
}
