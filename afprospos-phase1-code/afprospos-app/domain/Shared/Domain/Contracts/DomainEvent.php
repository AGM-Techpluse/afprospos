<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Contracts;

use DateTimeImmutable;

/**
 * Marker contract for domain events. A domain event describes a business
 * fact that has already happened (past tense), never a UI action or an
 * HTTP outcome (CPNC §3.3). Kept framework-free — no Illuminate imports —
 * so Domain/ never depends on Laravel's event dispatcher directly (ADD §7.3,
 * CPNC §4.1 Purity Law).
 */
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
