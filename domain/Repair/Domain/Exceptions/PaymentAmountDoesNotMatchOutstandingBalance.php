<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class PaymentAmountDoesNotMatchOutstandingBalance extends DomainException
{
    public static function forRepair(int $repairJobId, int $expectedMinor, int $givenMinor): self
    {
        return new self(
            "A payment recorded as fully paid must match the outstanding balance exactly — repair [{$repairJobId}] owes {$expectedMinor} minor units, {$givenMinor} was requested.",
        );
    }
}
