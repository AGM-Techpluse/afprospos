<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairSupplementalQuote;
use Domain\Repair\Domain\Repositories\RepairSupplementalQuoteRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairSupplementalQuoteId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairSupplementalQuoteRecord;
use Domain\Shared\Domain\ValueObjects\Money;

final class EloquentRepairSupplementalQuoteRepository implements RepairSupplementalQuoteRepository
{
    public function get(RepairSupplementalQuoteId $id): RepairSupplementalQuote
    {
        return $this->toDomain(RepairSupplementalQuoteRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(RepairSupplementalQuoteId $id): RepairSupplementalQuote
    {
        $record = RepairSupplementalQuoteRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(RepairSupplementalQuote $quote): RepairSupplementalQuoteId
    {
        $attributes = [
            'repair_job_id' => $quote->repairJobId()->value,
            'amount_minor' => $quote->amount()->minor,
            'reason' => $quote->reason(),
            'requested_by_staff_id' => $quote->requestedByStaffId(),
            'status' => $quote->status(),
            'approved_by_staff_id' => $quote->approvedByStaffId(),
            'decided_at' => $quote->decidedAt(),
        ];

        if ($quote->id() === null) {
            $record = RepairSupplementalQuoteRecord::query()->create($attributes);
        } else {
            $record = RepairSupplementalQuoteRecord::query()->findOrFail($quote->id()->value);
            $record->update($attributes);
        }

        return new RepairSupplementalQuoteId($record->id);
    }

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairSupplementalQuote
    {
        $record = RepairSupplementalQuoteRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function sumApprovedForJob(RepairJobId $repairJobId): int
    {
        return (int) RepairSupplementalQuoteRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->where('status', 'approved')
            ->sum('amount_minor');
    }

    private function toDomain(RepairSupplementalQuoteRecord $record): RepairSupplementalQuote
    {
        return RepairSupplementalQuote::reconstitute(
            new RepairSupplementalQuoteId($record->id),
            new RepairJobId($record->repair_job_id),
            new Money($record->amount_minor),
            $record->reason,
            $record->requested_by_staff_id,
            $record->status,
            $record->approved_by_staff_id,
            $record->decided_at?->toDateTimeImmutable(),
        );
    }
}
