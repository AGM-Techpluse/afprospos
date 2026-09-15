<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Entities;

use Domain\Collection\Domain\ValueObjects\CollectionCaseEventId;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Append-only (DBDD §12.2) — no mutators, mirrors AuditLogRecord's no-update/no-delete discipline at the domain level too. */
final class CollectionCaseEvent
{
    private function __construct(
        private readonly ?CollectionCaseEventId $id,
        private readonly CollectionCaseId $collectionCaseId,
        private readonly string $eventType,
        private readonly ?StaffId $actorStaffId,
        private readonly array $detail,
    ) {}

    public static function record(CollectionCaseId $collectionCaseId, string $eventType, ?StaffId $actorStaffId, array $detail): self
    {
        return new self(null, $collectionCaseId, $eventType, $actorStaffId, $detail);
    }

    public static function reconstitute(
        CollectionCaseEventId $id,
        CollectionCaseId $collectionCaseId,
        string $eventType,
        ?StaffId $actorStaffId,
        array $detail,
    ): self {
        return new self($id, $collectionCaseId, $eventType, $actorStaffId, $detail);
    }

    public function id(): ?CollectionCaseEventId
    {
        return $this->id;
    }

    public function collectionCaseId(): CollectionCaseId
    {
        return $this->collectionCaseId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function actorStaffId(): ?StaffId
    {
        return $this->actorStaffId;
    }

    public function detail(): array
    {
        return $this->detail;
    }
}
