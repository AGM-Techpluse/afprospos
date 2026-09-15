<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidRepairTransition extends DomainException
{
    public static function forJob(int $repairJobId, string $from, string $to): self
    {
        return new self("Repair job [{$repairJobId}] cannot transition from [{$from}] to [{$to}].");
    }
}
