<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Entities;

use Domain\Shared\Domain\ValueObjects\CustomerId;

final class CustomerAccount
{
    private function __construct(
        private readonly CustomerId $id,
        private readonly string $name,
        private readonly string $phone,
        private readonly ?string $email,
        private readonly string $status,
        private readonly bool $isRegistered,
    ) {}

    public static function reconstitute(
        CustomerId $id,
        string $name,
        string $phone,
        ?string $email,
        string $status,
        bool $isRegistered,
    ): self {
        return new self($id, $name, $phone, $email, $status, $isRegistered);
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** False for a walk-in row recorded during a sale/repair/diagnosis intake with no self-service account — it has no password and can never log in. */
    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }
}
