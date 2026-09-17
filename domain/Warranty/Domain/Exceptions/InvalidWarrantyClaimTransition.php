<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidWarrantyClaimTransition extends DomainException
{
    public static function forClaim(int $warrantyClaimId, string $from, string $to): self
    {
        return new self("Warranty claim [{$warrantyClaimId}] cannot transition from [{$from}] to [{$to}].");
    }
}
