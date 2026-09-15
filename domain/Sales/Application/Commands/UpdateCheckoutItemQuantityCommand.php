<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

final readonly class UpdateCheckoutItemQuantityCommand
{
    public function __construct(
        public int $checkoutId,
        public int $checkoutItemId,
        public int $quantity,
    ) {}
}
