<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A new catalog fault needs a device model to scope itself to — a legacy repair with no device model on record has none to anchor one to. */
final class RepairHasNoDeviceModel extends DomainException
{
    public static function forJob(int $repairJobId): self
    {
        return new self("Repair [{$repairJobId}] has no device model on record, so a new fault can't be added to its catalog.");
    }
}
