<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Commands;

final readonly class DeactivateShopCommand
{
    public function __construct(
        public int $shopId,
        public int $deactivatedByStaffId,
    ) {}
}
