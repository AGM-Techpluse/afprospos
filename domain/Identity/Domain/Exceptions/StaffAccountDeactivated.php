<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * BLD RBAC-BR-08: a deactivated staff member must not authenticate.
 * Maps to HTTP 403.
 */
final class StaffAccountDeactivated extends DomainException
{
    public static function forStaff(StaffId $id): self
    {
        return new self("Staff account [{$id->value}] is deactivated and cannot authenticate.");
    }
}
