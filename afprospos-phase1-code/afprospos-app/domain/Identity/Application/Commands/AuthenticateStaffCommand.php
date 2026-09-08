<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class AuthenticateStaffCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember,
    ) {}
}
