<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairDownPaymentApproval;
use Domain\Repair\Domain\Repositories\RepairDownPaymentApprovalRepository;
use Domain\Repair\Domain\ValueObjects\RepairDownPaymentApprovalId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDownPaymentApprovalRecord;
use Domain\Shared\Domain\ValueObjects\Money;

final class EloquentRepairDownPaymentApprovalRepository implements RepairDownPaymentApprovalRepository
{
    public function get(RepairDownPaymentApprovalId $id): RepairDownPaymentApproval
    {
        return $this->toDomain(RepairDownPaymentApprovalRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(RepairDownPaymentApprovalId $id): RepairDownPaymentApproval
    {
        $record = RepairDownPaymentApprovalRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(RepairDownPaymentApproval $approval): RepairDownPaymentApprovalId
    {
        $attributes = [
            'repair_job_id' => $approval->repairJobId()->value,
            'amount_minor' => $approval->amount()->minor,
            'method' => $approval->method(),
            'requested_by_staff_id' => $approval->requestedByStaffId(),
            'status' => $approval->status(),
            'approved_by_staff_id' => $approval->approvedByStaffId(),
            'decided_at' => $approval->decidedAt(),
        ];

        if ($approval->id() === null) {
            $record = RepairDownPaymentApprovalRecord::query()->create($attributes);
        } else {
            $record = RepairDownPaymentApprovalRecord::query()->findOrFail($approval->id()->value);
            $record->update($attributes);
        }

        return new RepairDownPaymentApprovalId($record->id);
    }

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairDownPaymentApproval
    {
        $record = RepairDownPaymentApprovalRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    private function toDomain(RepairDownPaymentApprovalRecord $record): RepairDownPaymentApproval
    {
        return RepairDownPaymentApproval::reconstitute(
            new RepairDownPaymentApprovalId($record->id),
            new RepairJobId($record->repair_job_id),
            new Money($record->amount_minor),
            $record->method,
            $record->requested_by_staff_id,
            $record->status,
            $record->approved_by_staff_id,
            $record->decided_at?->toDateTimeImmutable(),
        );
    }
}
