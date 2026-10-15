<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidDiagnosisCaseTransition extends DomainException
{
    public static function forCase(int $diagnosisCaseId, string $status): self
    {
        return new self("Diagnosis case [{$diagnosisCaseId}] cannot be reassigned a technician once it is [{$status}].");
    }
}
