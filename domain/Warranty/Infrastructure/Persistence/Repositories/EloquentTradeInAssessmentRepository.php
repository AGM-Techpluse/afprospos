<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Repositories;

use Domain\Warranty\Domain\Entities\TradeInAssessment;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;

final class EloquentTradeInAssessmentRepository implements TradeInAssessmentRepository
{
    public function get(TradeInAssessmentId $id): TradeInAssessment
    {
        return $this->toDomain(TradeInAssessmentRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(TradeInAssessmentId $id): TradeInAssessment
    {
        $record = TradeInAssessmentRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(TradeInAssessment $tradeIn): TradeInAssessmentId
    {
        $attributes = [
            'customer_id' => $tradeIn->customerId(),
            'related_checkout_id' => $tradeIn->relatedCheckoutId(),
            'device_description' => $tradeIn->deviceDescription(),
            'assessed_value_minor' => $tradeIn->assessedValueMinor(),
            'resolution_state' => $tradeIn->resolutionState(),
            'assessed_by_staff_id' => $tradeIn->assessedByStaffId(),
            'approved_by_staff_id' => $tradeIn->approvedByStaffId(),
        ];

        if ($tradeIn->id() === null) {
            $record = TradeInAssessmentRecord::query()->create($attributes);
        } else {
            $record = TradeInAssessmentRecord::query()->findOrFail($tradeIn->id()->value);
            $record->update($attributes);
        }

        return new TradeInAssessmentId($record->id);
    }

    private function toDomain(TradeInAssessmentRecord $record): TradeInAssessment
    {
        return TradeInAssessment::reconstitute(
            new TradeInAssessmentId($record->id),
            $record->customer_id,
            $record->related_checkout_id,
            $record->device_description,
            $record->assessed_value_minor,
            $record->resolution_state,
            $record->assessed_by_staff_id,
            $record->approved_by_staff_id,
        );
    }
}
