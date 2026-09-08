<?php

declare(strict_types=1);

namespace Domain\Audit\Application\Contracts;

use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Owned by the Audit module's Application layer (Dependency Inversion —
 * CPNC §4.4): any module that needs to record an audit fact depends on
 * this contract, never on the AuditLogRecord Eloquent model directly.
 *
 * Phase 1 scope: synchronous, direct writes from RBAC/Identity handlers.
 * Phase 8 upgrades the *source* of these calls (outbox-driven, broader
 * event coverage) without changing this contract's shape.
 */
interface AuditWriter
{
    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     * @param  array<string, mixed>|null  $context
     */
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
    ): void;
}
