<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Repositories;

use Domain\Warranty\Domain\Entities\TradeInAssessment;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;

interface TradeInAssessmentRepository
{
    public function get(TradeInAssessmentId $id): TradeInAssessment;

    /** Locks the row with SELECT ... FOR UPDATE — the serialization point for a duplicate assess/approve race. */
    public function lockForUpdate(TradeInAssessmentId $id): TradeInAssessment;

    public function save(TradeInAssessment $tradeIn): TradeInAssessmentId;
}
