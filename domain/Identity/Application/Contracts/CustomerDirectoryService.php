<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Contracts;

/**
 * The published cross-module write contract for linking a staff-captured
 * walk-in (a sale/repair/diagnosis intake's bare name+phone) to a real,
 * passwordless `CustomerRecord` — so the same person is findable via
 * `CustomerDirectoryQuery::search()` on a later visit. Sales/Repair depend
 * on this, never on Identity's Domain/Infrastructure internals directly
 * (CPNC §4.2).
 */
interface CustomerDirectoryService
{
    public function findOrCreateWalkIn(string $name, string $phone, ?string $email): int;
}
