<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\RenameMasterEntryCommand;
use Domain\Inventory\Domain\Exceptions\MasterEntryAlreadyExists;
use Domain\Inventory\Domain\Exceptions\MasterEntryNotFound;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Renaming a category or unit also rewrites the products that carry that name
 * (the repository does it in the same transaction), so no product is left
 * pointing at a name that no longer exists.
 */
final class RenameMasterEntryHandler
{
    public function __construct(
        private readonly InventoryMasterRepository $entries,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RenameMasterEntryCommand $command): void
    {
        $name = trim($command->name);

        $this->atomic->run(function () use ($command, $name): void {
            $entry = $this->entries->find($command->entryId) ?? throw MasterEntryNotFound::forId($command->entryId);

            if ($this->entries->nameExists($entry['kind'], $name, $command->entryId)) {
                throw MasterEntryAlreadyExists::forName($entry['kind'], $name);
            }

            $this->entries->rename($command->entryId, $name);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryMasterEntryRenamed',
                actorStaffId: new StaffId($command->renamedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_master_entry',
                subjectId: $command->entryId,
                beforeState: ['name' => $entry['name']],
                afterState: ['name' => $name],
            );
        });
    }
}
