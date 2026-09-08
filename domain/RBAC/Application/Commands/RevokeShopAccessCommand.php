<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Commands;

final readonly class RevokeShopAccessCommand
{
    public function __construct(
        public int $staffId,
        public int $shopId,
        public int $revokedByStaffId,
    ) {}
}
