<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReceiveStockCommand;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class ReceiveStockHandler
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReceiveStockCommand $command): void
    {
        $skuId = new SkuId($command->skuId);
        $shopId = new ShopId($command->shopId);
        $sku = $this->skus->get($skuId);

        if ($sku->isSerialized()) {
            foreach ($command->imeis ?? [] as $imei) {
                if ($this->items->existsWithImei($imei)) {
                    throw ImeiAlreadyExists::forImei($imei);
                }
            }
        }

        $this->atomic->run(function () use ($command, $skuId, $shopId, $sku): void {
            if ($sku->isSerialized()) {
                foreach ($command->imeis ?? [] as $imei) {
                    $this->items->create($skuId, $imei, $shopId, $command->condition);
                }
            } else {
                $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
                $level->receive($command->quantity ?? 0);
                $this->stockLevels->save($level);
            }

            $this->audit->record(
                module: 'Inventory',
                eventType: 'StockReceived',
                actorStaffId: new StaffId($command->receivedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: null,
                afterState: [
                    'shop_id' => $shopId->value,
                    'quantity' => $command->quantity,
                    'imeis' => $command->imeis,
                ],
            );
        });
    }
}
