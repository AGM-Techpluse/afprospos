<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Defense-in-depth against the expiry worker racing an authorization/down-payment confirmation — mirrors CheckoutReservationExpired's role. */
final class AuthorizationDeadlinePassed extends DomainException
{
    public static function forJob(int $repairJobId): self
    {
        return new self("Repair job [{$repairJobId}]'s authorization deadline has already passed.");
    }
}
