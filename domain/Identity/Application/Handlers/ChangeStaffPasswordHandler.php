<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\ChangeStaffPasswordCommand;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Self-service password change — the actor always changes their own password, never another staff member's (no admin-reset path exists here by design). */
final class ChangeStaffPasswordHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ChangeStaffPasswordCommand $command): void
    {
        $staffId = new StaffId($command->staffId);

        if (! $this->staff->verifyPassword($staffId, $command->currentPassword)) {
            throw InvalidCredentials::make();
        }

        $this->atomic->run(function () use ($command, $staffId): void {
            $this->staff->updatePassword($staffId, $command->newPassword);

            // Deliberately no before/after password material in the audit context — only that a change happened.
            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffPasswordChanged',
                actorStaffId: $staffId,
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: null,
                afterState: null,
            );
        });
    }
}
