<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Repositories;

use Domain\Identity\Domain\Entities\CustomerAccount;
use Domain\Shared\Domain\ValueObjects\CustomerId;

interface CustomerRepository
{
    public function findByPhone(string $phone): ?CustomerAccount;
    
    public function findByEmail(string $email): ?CustomerAccount;

    public function existsWithPhone(string $phone): bool;

    public function existsWithEmail(string $email): bool;

    public function verifyPassword(CustomerId $id, string $plainPassword): bool;

    public function create(string $name, string $phone, ?string $email, string $plainPassword): CustomerId;
}
