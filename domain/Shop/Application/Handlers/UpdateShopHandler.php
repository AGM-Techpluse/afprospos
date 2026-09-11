<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shop\Application\Commands\UpdateShopCommand;
use Domain\Shop\Domain\Exceptions\DuplicateShopSkuPrefixCode;
use Domain\Shop\Domain\Repositories\ShopRepository;

final class UpdateShopHandler
{
    public function __construct(
        private readonly ShopRepository $shops,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateShopCommand $command): void
    {
        $shopId = new ShopId($command->shopId);

        if ($this->shops->existsWithSkuPrefixCodeExcept($command->skuPrefixCode, $shopId)) {
            throw DuplicateShopSkuPrefixCode::forCode($command->skuPrefixCode);
        }

        $this->atomic->run(function () use ($command, $shopId): void {
            $this->shops->update(
                $shopId,
                $command->name,
                $command->skuPrefixCode,
                $command->address,
                $command->contactPhone,
                $command->contactEmail,
            );

            $this->audit->record(
                module: 'Shop',
                eventType: 'ShopUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'shop',
                subjectId: $shopId->value,
                beforeState: null,
                afterState: [
                    'name' => $command->name,
                    'sku_prefix_code' => $command->skuPrefixCode,
                ],
            );
        });
    }
}
