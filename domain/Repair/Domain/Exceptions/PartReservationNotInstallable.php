<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class PartReservationNotInstallable extends DomainException
{
    public static function forReservation(int $reservationId, string $status): self
    {
        return new self("Repair part reservation [{$reservationId}] cannot be installed from status [{$status}].");
    }

    public static function notReleasable(int $reservationId, string $status): self
    {
        return new self("Repair part reservation [{$reservationId}] cannot be released from status [{$status}].");
    }
}
