<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class DuplicateRoleName extends DomainException
{
    public static function forName(string $name): self
    {
        return new self("A role already exists named [{$name}].");
    }
}
