<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class MasterEntryInUse extends DomainException
{
    public static function forName(string $kind, string $name): self
    {
        return new self("This {$kind} [{$name}] is used by products and cannot be deleted. Rename it instead.");
    }
}
