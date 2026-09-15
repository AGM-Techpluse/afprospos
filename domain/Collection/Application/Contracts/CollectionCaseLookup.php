<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Contracts;

/** The published cross-module read contract — Repair's detail Query depends on this (and only this) to link to a device's collection case, never on Collection's Domain/Infrastructure internals directly (CPNC §4.2). */
interface CollectionCaseLookup
{
    /**
     * @return array{id: int, status: string, context: string, collection_deadline_at: string}|null
     */
    public function findLatestBySource(string $sourceType, int $sourceId): ?array;
}
