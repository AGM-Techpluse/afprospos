<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A product was saved with a category, unit or supplier that is not on the admin's list. */
final class UnknownMasterEntry extends DomainException
{
    public static function forName(string $kind, string $name): self
    {
        return new self("No {$kind} named [{$name}]. Add it under Settings → Inventory catalog first.");
    }
}
