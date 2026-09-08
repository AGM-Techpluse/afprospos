<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Repositories;

use Domain\RBAC\Domain\Entities\StaffShopGrant;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

interface StaffShopGrantRepository
{
    public function hasActiveGrant(StaffId $staffId, ShopId $shopId): bool;

    public function findActiveGrant(StaffId $staffId, ShopId $shopId): ?StaffShopGrant;

    public function save(StaffShopGrant $grant): void;
}
