<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Commands;

final readonly class UpdateRolePermissionsCommand
{
    /** @param  string[]  $permissionNames */
    public function __construct(
        public string $name,
        public array $permissionNames,
        public int $updatedByStaffId,
    ) {}
}
