<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Entities;

use Domain\Identity\Domain\Exceptions\StaffAlreadyDeactivated;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Pure domain entity — no Eloquent, no framework (CPNC §4.1 Purity Law).
 * Represents identity state and its lifecycle rule. Role/shop-scope
 * behaviour is deliberately NOT here — that is RBAC's aggregate concern,
 * this module only owns "who is this person and can they authenticate".
 */
final class StaffAccount
{
    private function __construct(
        private readonly StaffId $id,
        private readonly string $name,
        private readonly string $phone,
        private readonly string $email,
        private string $status,
    ) {}

    public static function reconstitute(
        StaffId $id,
        string $name,
        string $phone,
        string $email,
        string $status,
    ): self {
        return new self($id, $name, $phone, $email, $status);
    }

    public function id(): StaffId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * BLD RBAC-BR-08: deactivation prevents future access while preserving
     * history — the caller (Handler) is responsible for auditing this and
     * for invalidating any live session; this method only enforces the
     * state-transition invariant itself.
     */
    public function deactivate(): void
    {
        if (! $this->isActive()) {
            throw StaffAlreadyDeactivated::forStaff($this->id);
        }

        $this->status = 'deactivated';
    }
}
