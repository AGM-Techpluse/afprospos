<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shop\Application\Commands\ActivateShopCommand;
use Domain\Shop\Domain\Repositories\ShopRepository;

final class ActivateShopHandler
{
    public function __construct(
        private readonly ShopRepository $shops,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ActivateShopCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $shopId = new ShopId($command->shopId);
            $shop = $this->shops->get($shopId);

            $beforeState = ['status' => $shop->status()];

            // Domain entity enforces the state-transition invariant
            // (throws ShopAlreadyActive if already active).
            $shop->activate();

            $this->shops->save($shop);

            $this->audit->record(
                module: 'Shop',
                eventType: 'ShopActivated',
                actorStaffId: new StaffId($command->activatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'shop',
                subjectId: $shopId->value,
                beforeState: $beforeState,
                afterState: ['status' => 'active'],
            );
        });
    }
}
