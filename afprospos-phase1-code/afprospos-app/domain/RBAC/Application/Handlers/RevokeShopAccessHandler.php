<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Handlers;

use App\Support\Clock\Clock;
use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\RBAC\Application\Commands\RevokeShopAccessCommand;
use Domain\RBAC\Domain\Repositories\StaffShopGrantRepository;
use Domain\Shared\Domain\Exceptions\ShopScopeViolation;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class RevokeShopAccessHandler
{
    public function __construct(
        private readonly StaffShopGrantRepository $grants,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
        private readonly Clock $clock,
    ) {}

    public function handle(RevokeShopAccessCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);
            $shopId = new ShopId($command->shopId);

            $grant = $this->grants->findActiveGrant($staffId, $shopId);

            if ($grant === null) {
                throw ShopScopeViolation::forShop($shopId->value);
            }

            $grant->revoke($this->clock->now(), new StaffId($command->revokedByStaffId));
            $this->grants->save($grant);

            $this->audit->record(
                module: 'RBAC',
                eventType: 'ShopAccessRevoked',
                actorStaffId: new StaffId($command->revokedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: ['shop_id' => $shopId->value, 'active' => true],
                afterState: ['shop_id' => $shopId->value, 'active' => false],
            );
        });
    }
}
