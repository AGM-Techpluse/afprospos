<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Commands;

final readonly class CreateShopCommand
{
    /** @param array<string, mixed> $offlinePolicy */
    public function __construct(
        public string $name,
        public string $skuPrefixCode,
        public string $address,
        public string $contactPhone,
        public string $contactEmail,
        public array $offlinePolicy,
        public int $createdByStaffId,
    ) {}
}
