<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * Deliberately generic ("invalid credentials") rather than distinguishing
 * "unknown email" from "wrong password" in the message shown to the
 * caller — do not leak which part was wrong; that is a standard
 * authentication information-disclosure precaution. Maps to HTTP 422.
 */
final class InvalidCredentials extends DomainException
{
    public static function make(): self
    {
        return new self('The provided credentials do not match our records.');
    }
}
