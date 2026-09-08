<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class DuplicateStaffEmail extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self("A staff account already exists with email [{$email}].");
    }
}
