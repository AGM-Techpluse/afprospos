<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateSkuCommissionCommand;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateSkuCommissionHandler
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateSkuCommissionCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $skuId = new SkuId($command->skuId);
            $sku = $this->skus->get($skuId);
            $before = ['is_commissionable' => $sku->isCommissionable(), 'commission_amount_minor' => $sku->commissionAmount()?->minor];

            $sku->setCommission(
                $command->isCommissionable,
                $command->commissionAmountMinor !== null ? new Money($command->commissionAmountMinor) : null,
            );
            $this->skus->save($sku);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'SkuCommissionUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: $before,
                afterState: ['is_commissionable' => $command->isCommissionable, 'commission_amount_minor' => $command->commissionAmountMinor],
            );
        });
    }
}
