<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class UnknownRole extends DomainException
{
    public static function named(string $roleName): self
    {
        return new self("Role [{$roleName}] is not a configured role.");
    }
}
