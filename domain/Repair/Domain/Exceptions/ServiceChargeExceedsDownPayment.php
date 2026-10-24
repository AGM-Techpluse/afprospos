<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class ServiceChargeExceedsDownPayment extends DomainException
{
    public static function forRepair(int $repairJobId, int $serviceChargeMinor, int $confirmedDownPaymentMinor): self
    {
        return new self(
            "Service charge cannot exceed the confirmed down payment — repair [{$repairJobId}] has a confirmed down payment of {$confirmedDownPaymentMinor} minor units, {$serviceChargeMinor} was requested as the service charge.",
        );
    }
}
