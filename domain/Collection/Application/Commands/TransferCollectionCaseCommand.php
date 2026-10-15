<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

final readonly class TransferCollectionCaseCommand
{
    public function __construct(
        public int $collectionCaseId,
        public int $targetShopId,
        public int $transferredByStaffId,
        public ?string $reason = null,
    ) {}
}
