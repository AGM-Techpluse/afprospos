<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A repair can only be sent to quote once its diagnosis has been saved as repairable. */
final class DiagnosisNotRepairable extends DomainException
{
    public static function forJob(int $repairJobId): self
    {
        return new self("Repair [{$repairJobId}] has no saved repairable diagnosis yet. Save the diagnosis outcome before sending it to quote.");
    }
}
