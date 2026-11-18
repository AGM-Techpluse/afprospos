<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateProductMetadataCommand;
use Domain\Inventory\Domain\Exceptions\UnknownMasterEntry;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateProductMetadataHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly InventoryMasterRepository $masters,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateProductMetadataCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            if (! $this->masters->exists('category', $command->category)) {
                throw UnknownMasterEntry::forName('category', $command->category);
            }

            if (! $this->masters->exists('unit', $command->unit)) {
                throw UnknownMasterEntry::forName('unit', $command->unit);
            }

            $productId = new ProductId($command->productId);
            $product = $this->products->get($productId);
            $before = [
                'brand' => $product->brand(),
                'model' => $product->model(),
                'category' => $product->category(),
                'unit' => $product->unit(),
            ];

            $this->products->updateMetadata(
                $productId,
                $command->brand,
                $command->model,
                $command->category,
                $command->unit,
            );

            $this->audit->record(
                module: 'Inventory',
                eventType: 'ProductMetadataUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'product',
                subjectId: $productId->value,
                beforeState: $before,
                afterState: [
                    'brand' => $command->brand,
                    'model' => $command->model,
                    'category' => $command->category,
                    'unit' => $command->unit,
                ],
            );
        });
    }
}
