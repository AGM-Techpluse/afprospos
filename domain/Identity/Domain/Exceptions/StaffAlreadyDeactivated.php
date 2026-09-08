<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class StaffAlreadyDeactivated extends DomainException
{
    public static function forStaff(StaffId $id): self
    {
        return new self("Staff account [{$id->value}] is already deactivated.");
    }
}
