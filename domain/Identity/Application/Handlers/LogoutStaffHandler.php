<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\LogoutStaffCommand;
use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class LogoutStaffHandler
{
    public function __construct(
        private readonly StaffSessionGateway $sessions,
        private readonly AuditWriter $audit,
    ) {}

    public function handle(LogoutStaffCommand $command): void
    {
        $this->sessions->logout();

        if ($command->staffId === null) {
            return;
        }

        $this->audit->record(
            module: 'Identity',
            eventType: 'StaffLoggedOut',
            actorStaffId: new StaffId($command->staffId),
            actorRoleSnapshot: null,
            subjectType: 'staff',
            subjectId: $command->staffId,
            beforeState: null,
            afterState: null,
            context: $command->ipAddress !== null ? ['ip_address' => $command->ipAddress] : null,
        );
    }
}
