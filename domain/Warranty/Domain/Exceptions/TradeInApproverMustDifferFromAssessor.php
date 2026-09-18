<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** TRADE-BR-05: "Assessed trade-in values shall require authorization ... separate from the staff member who performed the assessment, to prevent a single employee from both setting and approving an inflated value." */
final class TradeInApproverMustDifferFromAssessor extends DomainException
{
    public static function forTradeIn(int $tradeInAssessmentId): self
    {
        return new self("Trade-in assessment [{$tradeInAssessmentId}] cannot be approved by the same staff member who assessed it.");
    }
}
