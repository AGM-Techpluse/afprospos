<?php

declare(strict_types=1);

namespace Domain\Identity\Domain\Repositories;

use Domain\Identity\Domain\Entities\StaffAccount;
use Domain\Shared\Domain\ValueObjects\StaffId;

interface StaffRepository
{
    public function get(StaffId $id): StaffAccount;

    /**
     * Returns the account regardless of active/deactivated status so the
     * calling Handler can produce the correct domain error (unknown email
     * vs. deactivated account) rather than a generic "invalid credentials".
     */
    public function findByEmail(string $email): ?StaffAccount;

    public function existsWithEmail(string $email): bool;

    public function existsWithEmailExcept(string $email, StaffId $exceptId): bool;

    public function verifyPassword(StaffId $id, string $plainPassword): bool;

    public function create(string $name, string $phone, string $email, string $plainPassword): StaffId;

    public function updateProfile(StaffId $id, string $name, string $phone, string $email): void;

    public function save(StaffAccount $staff): void;
}
