<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\AddProductVariantCommand;
use Domain\Inventory\Application\Services\SkuVariantWriter;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class AddProductVariantHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly SkuVariantWriter $skuWriter,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AddProductVariantCommand $command): SkuId
    {
        return $this->atomic->run(function () use ($command): SkuId {
            $productId = new ProductId($command->productId);
            $product = $this->products->get($productId);

            $result = $this->skuWriter->write(
                productId: $productId,
                shopCode: $command->shopCode,
                category: $product->category(),
                attributes: $command->attributes,
                isSerialized: $command->isSerialized,
                costPriceMinor: $command->costPriceMinor,
                markupPercent: $command->markupPercent,
                sellingPriceMinorOverride: $command->sellingPriceMinorOverride,
                lowStockThreshold: $command->lowStockThreshold,
                shopId: $command->shopId,
                condition: $command->condition,
                initialQuantity: $command->isSerialized ? null : $command->initialQuantity,
                initialImeis: $command->isSerialized ? $command->initialImeis : null,
                initialSerialNumbers: $command->isSerialized ? $command->initialSerialNumbers : null,
                isCommissionable: $command->isCommissionable,
                commissionAmountMinor: $command->commissionAmountMinor,
            );

            $this->audit->record(
                module: 'Inventory',
                eventType: 'ProductVariantAdded',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $result['skuId']->value,
                beforeState: null,
                afterState: ['product_id' => $productId->value, 'attributes' => $command->attributes],
            );

            return $result['skuId'];
        });
    }
}
