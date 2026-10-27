<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateSkuAttributesCommand;
use Domain\Inventory\Domain\Exceptions\DuplicateSkuAttributes;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Corrects a product's specs after creation (e.g. a mistyped storage size) — see Sku::updateAttributes(). */
final class UpdateSkuAttributesHandler
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateSkuAttributesCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $skuId = new SkuId($command->skuId);
            $sku = $this->skus->get($skuId);
            $before = $sku->attributes();

            // INV-BR-08: the new attributes must not collide with a DIFFERENT, already-existing
            // sibling SKU under the same product — that would silently merge two SKUs with
            // separate stock/pricing histories.
            $collision = $this->skus->findByProductAndAttributes($sku->productId(), $command->attributes, $sku->isSerialized());

            if ($collision !== null && ! $collision->id()->equals($skuId)) {
                throw DuplicateSkuAttributes::forProduct($sku->productId()->value);
            }

            $sku->updateAttributes($command->attributes);
            $this->skus->save($sku);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'SkuAttributesUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: ['attributes' => $before],
                afterState: ['attributes' => $command->attributes],
            );
        });
    }
}
