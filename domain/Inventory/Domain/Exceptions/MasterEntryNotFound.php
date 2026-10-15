<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class MasterEntryNotFound extends DomainException
{
    public static function forId(int $id): self
    {
        return new self("Inventory list entry [{$id}] does not exist.");
    }
}
