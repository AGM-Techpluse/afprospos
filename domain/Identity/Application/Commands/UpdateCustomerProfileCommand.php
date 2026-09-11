<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

use Domain\Shared\Domain\ValueObjects\CustomerId;

final readonly class UpdateCustomerProfileCommand
{
    public function __construct(
        public CustomerId $customerId,
        public string $name,
        public string $phone,
        public ?string $email,
    ) {}
}
