<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Commands;

final readonly class UpdateShopCommand
{
    public function __construct(
        public int $shopId,
        public string $name,
        public string $skuPrefixCode,
        public string $address,
        public string $contactPhone,
        public string $contactEmail,
        public int $updatedByStaffId,
    ) {}
}
