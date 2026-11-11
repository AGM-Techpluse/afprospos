<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateSkuPriceCommand;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateSkuPriceHandler
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateSkuPriceCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $skuId = new SkuId($command->skuId);
            $sku = $this->skus->get($skuId);
            $before = [
                'cost_price_minor' => $sku->costPrice()->minor,
                'markup_percent' => $sku->markupPercent(),
                'selling_price_minor' => $sku->sellingPrice()->minor,
                'selling_price_overridden' => $sku->sellingPriceOverridden(),
            ];

            $sku->updateCostAndMarkup(new Money($command->costPriceMinor), $command->markupPercent);

            if ($command->sellingPriceMinorOverride !== null) {
                $sku->overrideSellingPrice(new Money($command->sellingPriceMinorOverride));
            } else {
                $sku->clearSellingPriceOverride();
            }

            $this->skus->save($sku);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'SkuPriceUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: $before,
                afterState: [
                    'cost_price_minor' => $command->costPriceMinor,
                    'markup_percent' => $command->markupPercent,
                    'selling_price_minor' => $sku->sellingPrice()->minor,
                    'selling_price_overridden' => $sku->sellingPriceOverridden(),
                ],
            );
        });
    }
}
