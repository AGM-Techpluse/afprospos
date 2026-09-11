<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class DuplicateCustomerEmail extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self("A customer account already exists with email [{$email}].");
    }
}
