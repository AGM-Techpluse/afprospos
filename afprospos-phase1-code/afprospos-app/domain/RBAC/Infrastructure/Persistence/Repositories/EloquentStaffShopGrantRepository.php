<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Persistence\Repositories;

use Domain\RBAC\Domain\Entities\StaffShopGrant;
use Domain\RBAC\Domain\Repositories\StaffShopGrantRepository;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentStaffShopGrantRepository implements StaffShopGrantRepository
{
    public function hasActiveGrant(StaffId $staffId, ShopId $shopId): bool
    {
        return StaffShopGrantRecord::query()
            ->where('staff_id', $staffId->value)
            ->where('shop_id', $shopId->value)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function findActiveGrant(StaffId $staffId, ShopId $shopId): ?StaffShopGrant
    {
        $record = StaffShopGrantRecord::query()
            ->where('staff_id', $staffId->value)
            ->where('shop_id', $shopId->value)
            ->whereNull('revoked_at')
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function save(StaffShopGrant $grant): void
    {
        if ($grant->id() === null) {
            StaffShopGrantRecord::query()->create([
                'staff_id' => $grant->staffId()->value,
                'shop_id' => $grant->shopId()->value,
                'granted_by_staff_id' => $grant->grantedByStaffId()->value,
                'granted_at' => $grant->grantedAt(),
                'revoked_at' => null,
                'revoked_by_staff_id' => null,
            ]);

            return;
        }

        StaffShopGrantRecord::query()
            ->whereKey($grant->id())
            ->update([
                'revoked_at' => $grant->revokedAt(),
                'revoked_by_staff_id' => $grant->revokedByStaffId()?->value,
            ]);
    }

    private function toDomain(StaffShopGrantRecord $record): StaffShopGrant
    {
        return StaffShopGrant::reconstitute(
            id: $record->id,
            staffId: new StaffId($record->staff_id),
            shopId: new ShopId($record->shop_id),
            grantedByStaffId: new StaffId($record->granted_by_staff_id),
            grantedAt: $record->granted_at->toDateTimeImmutable(),
            revokedAt: $record->revoked_at?->toDateTimeImmutable(),
            revokedByStaffId: $record->revoked_by_staff_id !== null
                ? new StaffId($record->revoked_by_staff_id)
                : null,
        );
    }
}
