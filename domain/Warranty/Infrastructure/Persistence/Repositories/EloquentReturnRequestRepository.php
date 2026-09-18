<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Repositories;

use Domain\Warranty\Domain\Entities\ReturnRequest;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;

final class EloquentReturnRequestRepository implements ReturnRequestRepository
{
    public function get(ReturnRequestId $id): ReturnRequest
    {
        return $this->toDomain(ReturnRequestRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(ReturnRequestId $id): ReturnRequest
    {
        $record = ReturnRequestRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(ReturnRequest $returnRequest): ReturnRequestId
    {
        $attributes = [
            'sale_id' => $returnRequest->saleId(),
            'customer_id' => $returnRequest->customerId(),
            'resolution_state' => $returnRequest->resolutionState(),
            'return_window_expires_at' => $returnRequest->returnWindowExpiresAt(),
            'denial_reason' => $returnRequest->denialReason(),
            'override_approved_by_staff_id' => $returnRequest->overrideApprovedByStaffId(),
            'refund_transaction_id' => $returnRequest->refundTransactionId(),
        ];

        if ($returnRequest->id() === null) {
            $record = ReturnRequestRecord::query()->create($attributes);
        } else {
            $record = ReturnRequestRecord::query()->findOrFail($returnRequest->id()->value);
            $record->update($attributes);
        }

        return new ReturnRequestId($record->id);
    }

    private function toDomain(ReturnRequestRecord $record): ReturnRequest
    {
        return ReturnRequest::reconstitute(
            new ReturnRequestId($record->id),
            $record->sale_id,
            $record->customer_id,
            $record->resolution_state,
            $record->return_window_expires_at->toDateTimeImmutable(),
            $record->denial_reason,
            $record->override_approved_by_staff_id,
            $record->refund_transaction_id,
        );
    }
}
