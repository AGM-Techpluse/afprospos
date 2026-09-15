<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Repositories;

use Domain\Collection\Domain\Entities\CollectionCase;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;

interface CollectionCaseRepository
{
    public function get(CollectionCaseId $id): CollectionCase;

    /** Locks the case row with SELECT ... FOR UPDATE — the serialization point for a duplicate release/override race. */
    public function lockForUpdate(CollectionCaseId $id): CollectionCase;

    public function save(CollectionCase $case): CollectionCaseId;

    /** @return CollectionCase[] */
    public function findBySource(string $sourceType, int $sourceId): array;

    /** @return int[] */
    public function findDueForOverdue(int $limit): array;

    /** @return int[] */
    public function findDueForAbandonment(int $limit): array;

    /** @return int[] */
    public function findPendingOrOverdue(int $limit): array;
}
