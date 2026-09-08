<?php

declare(strict_types=1);

namespace Domain\Audit\Infrastructure\Persistence;

use App\Support\Clock\Clock;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Audit\Infrastructure\Persistence\Eloquent\AuditLogRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentAuditWriter implements AuditWriter
{
    public function __construct(private readonly Clock $clock) {}

    public function record(
        string $module,
        string $eventType,
        ?StaffId $actorStaffId,
        ?string $actorRoleSnapshot,
        string $subjectType,
        int $subjectId,
        ?array $beforeState,
        ?array $afterState,
        ?array $context = null,
    ): void {
        AuditLogRecord::query()->create([
            'module' => $module,
            'event_type' => $eventType,
            'actor_staff_id' => $actorStaffId?->value,
            'actor_role_snapshot' => $actorRoleSnapshot,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'context' => $context,
            'created_at' => $this->clock->now(),
        ]);
    }
}
