<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\TransferCollectionCaseCommand;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Exceptions\CollectionTransferNotAllowed;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Shared\Domain\Contracts\ShopClassification;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class TransferCollectionCaseHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly ShopClassification $shops,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(TransferCollectionCaseCommand $command): void
    {
        if ($this->shops->isWarehouse(new ShopId($command->targetShopId))) {
            throw CollectionTransferNotAllowed::warehouse($command->targetShopId);
        }

        $this->atomic->run(function () use ($command): void {
            $caseId = new CollectionCaseId($command->collectionCaseId);
            $case = $this->cases->lockForUpdate($caseId);
            $fromShopId = $case->shopId();
            $actor = new StaffId($command->transferredByStaffId);

            $case->transferToShop($command->targetShopId);
            $this->cases->save($case);

            $this->events->save(CollectionCaseEvent::record(
                $caseId,
                'collection_transferred',
                $actor,
                [
                    'from_shop_id' => $fromShopId,
                    'to_shop_id' => $command->targetShopId,
                    'reason' => $command->reason,
                ],
            ));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionCaseTransferred',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $caseId->value,
                beforeState: ['shop_id' => $fromShopId],
                afterState: ['shop_id' => $command->targetShopId, 'reason' => $command->reason],
            );
        });
    }
}
