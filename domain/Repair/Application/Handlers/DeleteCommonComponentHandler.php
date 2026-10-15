<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteCommonComponentCommand;
use Domain\Repair\Domain\Repositories\CommonComponentRepository;
use Domain\Repair\Domain\ValueObjects\CommonComponentId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Any fault that already recorded this component keeps its label_snapshot (migration's nullOnDelete). */
final class DeleteCommonComponentHandler
{
    public function __construct(
        private readonly CommonComponentRepository $components,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteCommonComponentCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CommonComponentId($command->commonComponentId);
            $component = $this->components->get($id);

            $this->components->delete($id);

            $this->audit->record(
                module: 'Repair',
                eventType: 'CommonComponentDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'common_component',
                subjectId: $id->value,
                beforeState: ['label' => $component->label()],
                afterState: null,
            );
        });
    }
}
