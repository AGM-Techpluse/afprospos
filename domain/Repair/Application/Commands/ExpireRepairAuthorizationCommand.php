<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Console-command-facing — one call per candidate job the ExpireRepairAuthorizations worker finds. */
final readonly class ExpireRepairAuthorizationCommand
{
    public function __construct(
        public int $repairJobId,
    ) {}
}
