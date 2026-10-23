<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateProductDescriptionCommand;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Storefront-facing copy, editable independently of the create-time fields (CPNC: products use findOrCreate, so there's no general "edit product" form today). */
final class UpdateProductDescriptionHandler
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateProductDescriptionCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $productId = new ProductId($command->productId);

            $this->products->setDescription($productId, $command->description);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'ProductDescriptionUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'product',
                subjectId: $productId->value,
                beforeState: null,
                afterState: ['description' => $command->description],
            );
        });
    }
}
