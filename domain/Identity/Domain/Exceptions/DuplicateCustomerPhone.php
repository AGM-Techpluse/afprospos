<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class DuplicateCustomerPhone extends DomainException
{
    public static function forPhone(string $phone): self
    {
        return new self("A customer account already exists with phone [{$phone}].");
    }
}
