<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\CreateMasterEntryCommand;
use Domain\Inventory\Domain\Exceptions\MasterEntryAlreadyExists;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateMasterEntryHandler
{
    public function __construct(
        private readonly InventoryMasterRepository $entries,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateMasterEntryCommand $command): int
    {
        $name = trim($command->name);

        return $this->atomic->run(function () use ($command, $name): int {
            if ($this->entries->nameExists($command->kind, $name)) {
                throw MasterEntryAlreadyExists::forName($command->kind, $name);
            }

            $id = $this->entries->create($command->kind, $name, $command->contactPhone, $command->contactEmail);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryMasterEntryCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_master_entry',
                subjectId: $id,
                beforeState: null,
                afterState: ['kind' => $command->kind, 'name' => $name],
            );

            return $id;
        });
    }
}
