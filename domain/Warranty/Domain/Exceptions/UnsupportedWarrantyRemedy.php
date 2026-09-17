<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** `exchange` is a legitimate BLD WAR-BR-06 remedy but is handled as a Trade-In-style transaction (BLD §5.7) — not yet built. Thrown from the Application layer (not the entity), since this is a current implementation gap, not a genuine business invariant. */
final class UnsupportedWarrantyRemedy extends DomainException
{
    public static function forRemedy(string $remedy): self
    {
        return new self("Remedy [{$remedy}] is not yet supported — exchange is handled by the Trade-In flow, which has not shipped yet.");
    }
}
