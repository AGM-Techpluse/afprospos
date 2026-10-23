<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class FindOrCreateWalkInCustomerCommand
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
    ) {}
}
