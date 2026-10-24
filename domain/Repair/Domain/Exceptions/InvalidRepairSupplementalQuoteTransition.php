<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidRepairSupplementalQuoteTransition extends DomainException
{
    public static function forQuote(int $repairSupplementalQuoteId, string $status): self
    {
        return new self("Supplemental quote [{$repairSupplementalQuoteId}] is already [{$status}] and cannot be decided again.");
    }
}
