<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class RepairJobIsClosed extends DomainException
{
    public static function forJob(int $repairJobId, string $status): self
    {
        return new self("Repair job [{$repairJobId}] is already {$status} and cannot be modified.");
    }
}
