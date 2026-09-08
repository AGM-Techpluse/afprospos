<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Commands;

final readonly class AssignRoleToStaffCommand
{
    public function __construct(
        public int $staffId,
        public string $roleName,
        public int $assignedByStaffId,
    ) {}
}
