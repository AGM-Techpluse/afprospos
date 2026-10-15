<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\DeleteMasterEntryCommand;
use Domain\Inventory\Domain\Exceptions\MasterEntryInUse;
use Domain\Inventory\Domain\Exceptions\MasterEntryNotFound;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DeleteMasterEntryHandler
{
    public function __construct(
        private readonly InventoryMasterRepository $entries,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteMasterEntryCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $entry = $this->entries->find($command->entryId) ?? throw MasterEntryNotFound::forId($command->entryId);

            if ($this->entries->isInUse($entry['kind'], $command->entryId, $entry['name'])) {
                throw MasterEntryInUse::forName($entry['kind'], $entry['name']);
            }

            $this->entries->delete($command->entryId);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryMasterEntryDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_master_entry',
                subjectId: $command->entryId,
                beforeState: ['kind' => $entry['kind'], 'name' => $entry['name']],
                afterState: null,
            );
        });
    }
}
