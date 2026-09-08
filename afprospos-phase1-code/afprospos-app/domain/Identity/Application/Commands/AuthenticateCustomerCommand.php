<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class AuthenticateCustomerCommand
{
    public function __construct(
        public string $phone,
        public string $password,
        public bool $remember,
    ) {}
}
