<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** INV-BR-12: IMEI is globally unique across the business, not merely within a shop. */
final class ImeiAlreadyExists extends DomainException
{
    public static function forImei(string $imei): self
    {
        return new self("An inventory item already exists with IMEI [{$imei}].");
    }
}
