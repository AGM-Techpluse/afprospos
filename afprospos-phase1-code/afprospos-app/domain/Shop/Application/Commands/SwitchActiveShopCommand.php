<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Commands;

final readonly class SwitchActiveShopCommand
{
    public function __construct(
        public int $staffId,
        public int $requestedShopId,
    ) {}
}
