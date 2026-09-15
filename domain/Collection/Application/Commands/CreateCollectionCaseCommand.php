<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

/** The single case-creation contract used by Repair (and later Warranty) — mirrors ReserveInventoryCommand's role as the shared cross-module write contract's parameter type. */
final readonly class CreateCollectionCaseCommand
{
    /**
     * @param  'repair_job'|'warranty_claim'  $sourceType
     * @param  'ready_for_collection'|'ready_for_return'  $context
     */
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public int $shopId,
        public int $originatingShopId,
        public string $context,
        public int $deadlineDays,
    ) {}
}
