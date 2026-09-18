<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidTradeInAssessmentTransition extends DomainException
{
    public static function forTradeIn(int $tradeInAssessmentId, string $from, string $to): self
    {
        return new self("Trade-in assessment [{$tradeInAssessmentId}] cannot transition from [{$from}] to [{$to}].");
    }
}
