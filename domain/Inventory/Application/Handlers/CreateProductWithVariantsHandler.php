<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\CreateProductWithVariantsCommand;
use Domain\Inventory\Application\Services\SkuVariantWriter;
use Domain\Inventory\Domain\Exceptions\UnknownMasterEntry;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateProductWithVariantsHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly InventoryMasterRepository $masters,
        private readonly SkuVariantWriter $skuWriter,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateProductWithVariantsCommand $command): ProductId
    {
        return $this->atomic->run(function () use ($command): ProductId {
            if (! $this->masters->exists('category', $command->category)) {
                throw UnknownMasterEntry::forName('category', $command->category);
            }

            if (! $this->masters->exists('unit', $command->unit)) {
                throw UnknownMasterEntry::forName('unit', $command->unit);
            }

            $productId = $this->products->findOrCreate(
                $command->brand,
                $command->model,
                $command->category,
                $command->unit,
                $command->purpose,
                $command->supplierId,
            );

            if ($command->imagePath !== null) {
                $this->products->setImagePath($productId, $command->imagePath);
            }

            $skuIds = [];

            foreach ($command->variants as $variant) {
                $result = $this->skuWriter->write(
                    productId: $productId,
                    shopCode: $command->shopCode,
                    category: $command->category,
                    attributes: $variant->attributes,
                    isSerialized: $command->isSerialized,
                    costPriceMinor: $variant->costPriceMinor,
                    markupPercent: $variant->markupPercent,
                    sellingPriceMinorOverride: $variant->sellingPriceMinorOverride,
                    lowStockThreshold: $variant->lowStockThreshold,
                    shopId: $command->shopId,
                    condition: $command->condition,
                    initialQuantity: $command->isSerialized ? null : $variant->initialQuantity,
                    initialImeis: $command->isSerialized ? $variant->initialImeis : null,
                    initialSerialNumbers: $command->isSerialized ? $variant->initialSerialNumbers : null,
                    isCommissionable: $variant->isCommissionable,
                    commissionAmountMinor: $variant->commissionAmountMinor,
                );

                $skuIds[] = $result['skuId']->value;
            }

            $this->audit->record(
                module: 'Inventory',
                eventType: 'ProductCreatedWithVariants',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'product',
                subjectId: $productId->value,
                beforeState: null,
                afterState: ['brand' => $command->brand, 'model' => $command->model, 'sku_ids' => $skuIds, 'variant_count' => count($skuIds)],
            );

            return $productId;
        });
    }
}
