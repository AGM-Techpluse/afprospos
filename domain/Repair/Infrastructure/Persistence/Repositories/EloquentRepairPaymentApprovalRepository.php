<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairPaymentApproval;
use Domain\Repair\Domain\Repositories\RepairPaymentApprovalRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPaymentApprovalId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPaymentApprovalRecord;
use Domain\Shared\Domain\ValueObjects\Money;

final class EloquentRepairPaymentApprovalRepository implements RepairPaymentApprovalRepository
{
    public function get(RepairPaymentApprovalId $id): RepairPaymentApproval
    {
        return $this->toDomain(RepairPaymentApprovalRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(RepairPaymentApprovalId $id): RepairPaymentApproval
    {
        $record = RepairPaymentApprovalRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(RepairPaymentApproval $approval): RepairPaymentApprovalId
    {
        $attributes = [
            'repair_job_id' => $approval->repairJobId()->value,
            'requested_financial_status' => $approval->requestedFinancialStatus(),
            'amount_minor' => $approval->amount()->minor,
            'method' => $approval->method(),
            'requested_by_staff_id' => $approval->requestedByStaffId(),
            'status' => $approval->status(),
            'approved_by_staff_id' => $approval->approvedByStaffId(),
            'decided_at' => $approval->decidedAt(),
        ];

        if ($approval->id() === null) {
            $record = RepairPaymentApprovalRecord::query()->create($attributes);
        } else {
            $record = RepairPaymentApprovalRecord::query()->findOrFail($approval->id()->value);
            $record->update($attributes);
        }

        return new RepairPaymentApprovalId($record->id);
    }

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairPaymentApproval
    {
        $record = RepairPaymentApprovalRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    private function toDomain(RepairPaymentApprovalRecord $record): RepairPaymentApproval
    {
        return RepairPaymentApproval::reconstitute(
            new RepairPaymentApprovalId($record->id),
            new RepairJobId($record->repair_job_id),
            $record->requested_financial_status,
            new Money($record->amount_minor),
            $record->method,
            $record->requested_by_staff_id,
            $record->status,
            $record->approved_by_staff_id,
            $record->decided_at?->toDateTimeImmutable(),
        );
    }
}
