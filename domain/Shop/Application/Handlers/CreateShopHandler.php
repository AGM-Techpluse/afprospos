<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shop\Application\Commands\CreateShopCommand;
use Domain\Shop\Domain\Exceptions\DuplicateShopSkuPrefixCode;
use Domain\Shop\Domain\Repositories\ShopRepository;

final class CreateShopHandler
{
    public function __construct(
        private readonly ShopRepository $shops,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateShopCommand $command): ShopId
    {
        if ($this->shops->existsWithSkuPrefixCode($command->skuPrefixCode)) {
            throw DuplicateShopSkuPrefixCode::forCode($command->skuPrefixCode);
        }

        return $this->atomic->run(function () use ($command): ShopId {
            $shopId = $this->shops->create(
                $command->name,
                $command->skuPrefixCode,
                $command->address,
                $command->contactPhone,
                $command->contactEmail,
                $command->offlinePolicy,
            );

            $this->audit->record(
                module: 'Shop',
                eventType: 'ShopCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'shop',
                subjectId: $shopId->value,
                beforeState: null,
                afterState: [
                    'name' => $command->name,
                    'sku_prefix_code' => $command->skuPrefixCode,
                ],
            );

            return $shopId;
        });
    }
}
