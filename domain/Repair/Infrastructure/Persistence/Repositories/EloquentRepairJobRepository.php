<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Carbon;

final class EloquentRepairJobRepository implements RepairJobRepository
{
    public function get(RepairJobId $id): RepairJob
    {
        return $this->toDomain(RepairJobRecord::query()->findOrFail($id->value));
    }

    /** Locks the job row (mirrors EloquentSalesCheckoutRepository::lockForUpdate) — the serialization point for authorization-expiry racing a payment confirmation. */
    public function lockForUpdate(RepairJobId $id): RepairJob
    {
        $record = RepairJobRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(RepairJob $repairJob): RepairJobId
    {
        $attributes = [
            'shop_id' => $repairJob->shopId()->value,
            'customer_id' => $repairJob->customerId()->value,
            'device_make' => $repairJob->deviceMake(),
            'device_model' => $repairJob->deviceModel(),
            'technician_staff_id' => $repairJob->technicianStaffId()?->value,
            'labour_charge_minor' => $repairJob->labourCharge()->minor,
            'down_payment_required_minor' => $repairJob->downPaymentRequired()?->minor,
            'down_payment_deadline_at' => $repairJob->downPaymentDeadlineAt(),
            'repair_status' => $repairJob->status(),
            'financial_status' => $repairJob->financialStatus(),
            'estimated_collection_date' => $repairJob->estimatedCollectionDate(),
            'unrepairable_settlement_state' => $repairJob->unrepairableSettlementState(),
        ];

        if ($repairJob->id() === null) {
            $record = RepairJobRecord::query()->create($attributes);
        } else {
            $record = RepairJobRecord::query()->findOrFail($repairJob->id()->value);
            $record->update($attributes);
        }

        return new RepairJobId($record->id);
    }

    public function findExpirableAuthorizationIds(int $limit): array
    {
        return RepairJobRecord::query()
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('repair_status', 'awaiting_authorization')
                        ->where('down_payment_deadline_at', '<=', Carbon::now());
                })->orWhere('repair_status', 'payment_overdue');
            })
            ->orderBy('down_payment_deadline_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    private function toDomain(RepairJobRecord $record): RepairJob
    {
        return RepairJob::reconstitute(
            new RepairJobId($record->id),
            new ShopId($record->shop_id),
            new CustomerId($record->customer_id),
            $record->device_make,
            $record->device_model,
            $record->technician_staff_id !== null ? new StaffId($record->technician_staff_id) : null,
            new Money($record->labour_charge_minor),
            $record->down_payment_required_minor !== null ? new Money($record->down_payment_required_minor) : null,
            $record->down_payment_deadline_at?->toDateTimeImmutable(),
            $record->repair_status,
            $record->financial_status,
            $record->estimated_collection_date?->toDateTimeImmutable(),
            $record->unrepairable_settlement_state,
        );
    }
}
