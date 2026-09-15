<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Thrown by the gateway resolver when no adapter is registered for a method — this is how `in_app` stays a legal enum value with no real online gateway yet (Phase 5 scope). */
final class UnsupportedPaymentMethod extends DomainException
{
    public static function forMethod(string $method): self
    {
        return new self("No payment gateway is registered for method [{$method}].");
    }
}
