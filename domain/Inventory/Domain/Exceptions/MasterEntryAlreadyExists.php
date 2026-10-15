<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class MasterEntryAlreadyExists extends DomainException
{
    public static function forName(string $kind, string $name): self
    {
        return new self(ucfirst($kind)." [{$name}] already exists.");
    }
}
