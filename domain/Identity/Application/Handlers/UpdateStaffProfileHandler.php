<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\UpdateStaffProfileCommand;
use Domain\Identity\Domain\Exceptions\DuplicateStaffEmail;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateStaffProfileHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateStaffProfileCommand $command): void
    {
        $staffId = new StaffId($command->staffId);

        if ($this->staff->existsWithEmailExcept($command->email, $staffId)) {
            throw DuplicateStaffEmail::forEmail($command->email);
        }

        $this->atomic->run(function () use ($command, $staffId): void {
            $this->staff->updateProfile($staffId, $command->name, $command->phone, $command->email);

            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffProfileUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: null,
                afterState: [
                    'name' => $command->name,
                    'phone' => $command->phone,
                    'email' => $command->email,
                ],
            );
        });
    }
}
