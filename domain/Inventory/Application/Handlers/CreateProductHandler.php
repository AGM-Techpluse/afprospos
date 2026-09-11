<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Domain\Exceptions\DuplicateSkuCode;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\Services\SkuGenerator;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly SkuRepository $skus,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateProductCommand $command): SkuId
    {
        return $this->atomic->run(function () use ($command): SkuId {
            $productId = $this->products->findOrCreate($command->brand, $command->model, $command->category);

            $sequence = $this->skus->nextSequence($command->shopCode, $command->category);
            $skuCode = SkuGenerator::generate($command->shopCode, $command->category, $sequence);

            if ($this->skus->existsWithCode($skuCode)) {
                throw DuplicateSkuCode::forCode($skuCode);
            }

            $costPrice = new Money($command->costPriceMinor);
            $sellingPriceOverridden = $command->sellingPriceMinorOverride !== null;
            $sellingPrice = $sellingPriceOverridden
                ? new Money($command->sellingPriceMinorOverride)
                : $costPrice->withMarkupPercent($command->markupPercent);

            $skuId = $this->skus->create(
                productId: $productId,
                skuCode: $skuCode,
                attributes: $command->attributes,
                isSerialized: $command->isSerialized,
                costPrice: $costPrice,
                markupPercent: $command->markupPercent,
                sellingPrice: $sellingPrice,
                sellingPriceOverridden: $sellingPriceOverridden,
                lowStockThreshold: $command->lowStockThreshold,
            );

            $this->receiveInitialStock($command, $skuId);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'ProductCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: null,
                afterState: ['sku_code' => $skuCode, 'brand' => $command->brand, 'model' => $command->model],
            );

            return $skuId;
        });
    }

    private function receiveInitialStock(CreateProductCommand $command, SkuId $skuId): void
    {
        $shopId = new ShopId($command->shopId);

        if ($command->isSerialized) {
            foreach ($command->initialImeis ?? [] as $imei) {
                $this->items->create($skuId, $imei, $shopId, $command->condition);
            }

            return;
        }

        if (($command->initialQuantity ?? 0) > 0) {
            $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
            $level->receive($command->initialQuantity);
            $this->stockLevels->save($level);
        }
    }
}
