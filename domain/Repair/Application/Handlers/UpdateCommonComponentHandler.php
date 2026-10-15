<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateCommonComponentCommand;
use Domain\Repair\Domain\Repositories\CommonComponentRepository;
use Domain\Repair\Domain\ValueObjects\CommonComponentId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateCommonComponentHandler
{
    public function __construct(
        private readonly CommonComponentRepository $components,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateCommonComponentCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CommonComponentId($command->commonComponentId);
            $component = $this->components->get($id);
            $before = ['label' => $component->label(), 'commission_amount_minor' => $component->commissionAmountMinor()];

            $component->rename($command->label);
            $component->reorder($command->sortOrder);
            $component->setCommissionAmount($command->commissionAmountMinor);
            $this->components->save($component);

            $this->audit->record(
                module: 'Repair',
                eventType: 'CommonComponentUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'common_component',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['label' => $command->label, 'commission_amount_minor' => $command->commissionAmountMinor],
            );
        });
    }
}
