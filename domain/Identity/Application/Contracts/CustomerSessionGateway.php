<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Contracts;

use Domain\Shared\Domain\ValueObjects\CustomerId;

interface CustomerSessionGateway
{
    public function login(CustomerId $id, bool $remember): void;

    public function logout(): void;
}
