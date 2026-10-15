<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateCommonComponentCommand;
use Domain\Repair\Domain\Entities\CommonComponent;
use Domain\Repair\Domain\Repositories\CommonComponentRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateCommonComponentHandler
{
    public function __construct(
        private readonly CommonComponentRepository $components,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateCommonComponentCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $component = CommonComponent::create($command->label, $command->sortOrder, $command->commissionAmountMinor);
            $id = $this->components->save($component);

            $this->audit->record(
                module: 'Repair',
                eventType: 'CommonComponentCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'common_component',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['label' => $command->label, 'commission_amount_minor' => $command->commissionAmountMinor],
            );

            return $id->value;
        });
    }
}
