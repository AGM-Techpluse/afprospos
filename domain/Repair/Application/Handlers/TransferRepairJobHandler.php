<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Contracts\CollectionCaseLookup;
use Domain\Repair\Application\Commands\TransferRepairJobCommand;
use Domain\Repair\Application\Services\ReleaseRepairPartsService;
use Domain\Repair\Domain\Exceptions\RepairTransferNotAllowed;
use Domain\Repair\Domain\Exceptions\WarehouseCannotTakeRepairs;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\Contracts\ShopClassification;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class TransferRepairJobHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairPartReservationRepository $partReservations,
        private readonly ReleaseRepairPartsService $releaseParts,
        private readonly CollectionCaseLookup $collectionCases,
        private readonly ShopClassification $shops,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(TransferRepairJobCommand $command): void
    {
        if ($this->shops->isWarehouse(new ShopId($command->targetShopId))) {
            throw WarehouseCannotTakeRepairs::forShop($command->targetShopId);
        }

        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $fromShopId = $job->shopId()->value;

            if ($fromShopId === $command->targetShopId) {
                throw RepairTransferNotAllowed::sameShop($fromShopId);
            }

            $collectionCase = $this->collectionCases->findLatestBySource('repair_job', $jobId->value);

            if ($collectionCase !== null && $collectionCase['status'] !== 'resolved') {
                throw RepairTransferNotAllowed::collectionOpen($jobId->value);
            }

            $hasReservedParts = collect($this->partReservations->findByRepairJob($jobId))
                ->contains(static fn ($reservation): bool => $reservation->status() === 'reserved');

            if ($hasReservedParts && ! $command->releaseReservedParts) {
                throw RepairTransferNotAllowed::reservedParts($jobId->value);
            }

            if ($hasReservedParts) {
                $this->releaseParts->releaseOutstanding($jobId, $fromShopId);
            }

            $job->transferToShop(new ShopId($command->targetShopId));
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairTransferred',
                actorStaffId: new StaffId($command->transferredByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: ['shop_id' => $fromShopId],
                afterState: [
                    'shop_id' => $command->targetShopId,
                    'released_reserved_parts' => $hasReservedParts,
                    'reason' => $command->reason,
                ],
            );
        });
    }
}
