<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidDiagnosisFeeState extends DomainException
{
    public static function forJob(int $repairJobId, string $reason): self
    {
        return new self("Diagnosis fee for repair [{$repairJobId}]: {$reason}");
    }
}
