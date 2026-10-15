<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Services\SkuVariantWriter;
use Domain\Inventory\Domain\Exceptions\UnknownMasterEntry;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly InventoryMasterRepository $masters,
        private readonly SkuVariantWriter $skuWriter,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateProductCommand $command): SkuId
    {
        return $this->atomic->run(function () use ($command): SkuId {
            // A product can only be filed under a category, unit and supplier the admin has defined.
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

            $result = $this->skuWriter->write(
                productId: $productId,
                shopCode: $command->shopCode,
                category: $command->category,
                attributes: $command->attributes,
                isSerialized: $command->isSerialized,
                costPriceMinor: $command->costPriceMinor,
                markupPercent: $command->markupPercent,
                sellingPriceMinorOverride: $command->sellingPriceMinorOverride,
                lowStockThreshold: $command->lowStockThreshold,
                shopId: $command->shopId,
                condition: $command->condition,
                initialQuantity: $command->initialQuantity,
                initialImeis: $command->initialImeis,
                initialSerialNumbers: $command->initialSerialNumbers,
                isCommissionable: $command->isCommissionable,
                commissionAmountMinor: $command->commissionAmountMinor,
            );

            $this->audit->record(
                module: 'Inventory',
                eventType: $result['wasExisting'] ? 'ProductRestocked' : 'ProductCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $result['skuId']->value,
                beforeState: null,
                afterState: ['brand' => $command->brand, 'model' => $command->model, 'attributes' => $command->attributes],
            );

            return $result['skuId'];
        });
    }
}
