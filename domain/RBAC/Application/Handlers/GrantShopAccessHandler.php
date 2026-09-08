<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Handlers;

use Domain\Shared\Domain\Contracts\Clock;
use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\RBAC\Application\Commands\GrantShopAccessCommand;
use Domain\RBAC\Domain\Entities\StaffShopGrant;
use Domain\RBAC\Domain\Repositories\StaffShopGrantRepository;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class GrantShopAccessHandler
{
    public function __construct(
        private readonly StaffShopGrantRepository $grants,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
        private readonly Clock $clock,
    ) {}

    public function handle(GrantShopAccessCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);
            $shopId = new ShopId($command->shopId);

            // Idempotent: granting access to an already-granted shop is a
            // no-op rather than a duplicate row or a thrown error — the
            // caller (e.g. re-submitting a staff-edit form) should not
            // need to know whether the grant already existed.
            if ($this->grants->hasActiveGrant($staffId, $shopId)) {
                return;
            }

            $grant = StaffShopGrant::grant(
                staffId: $staffId,
                shopId: $shopId,
                grantedByStaffId: new StaffId($command->grantedByStaffId),
                grantedAt: $this->clock->now(),
            );

            $this->grants->save($grant);

            $this->audit->record(
                module: 'RBAC',
                eventType: 'ShopAccessGranted',
                actorStaffId: new StaffId($command->grantedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: null,
                afterState: ['shop_id' => $shopId->value],
            );
        });
    }
}
