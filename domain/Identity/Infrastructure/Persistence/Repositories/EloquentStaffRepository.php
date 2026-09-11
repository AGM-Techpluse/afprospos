<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Persistence\Repositories;

use Domain\Identity\Domain\Entities\StaffAccount;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Support\Facades\Hash;

final class EloquentStaffRepository implements StaffRepository
{
    public function get(StaffId $id): StaffAccount
    {
        $record = StaffRecord::query()->findOrFail($id->value);

        return $this->toDomain($record);
    }

    public function findByEmail(string $email): ?StaffAccount
    {
        $record = StaffRecord::query()->where('email', $email)->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function existsWithEmail(string $email): bool
    {
        return StaffRecord::query()->where('email', $email)->exists();
    }

    public function existsWithEmailExcept(string $email, StaffId $exceptId): bool
    {
        return StaffRecord::query()
            ->where('email', $email)
            ->where('id', '!=', $exceptId->value)
            ->exists();
    }

    public function verifyPassword(StaffId $id, string $plainPassword): bool
    {
        $record = StaffRecord::query()->findOrFail($id->value);

        return Hash::check($plainPassword, $record->password);
    }

    public function create(string $name, string $phone, string $email, string $plainPassword): StaffId
    {
        $record = StaffRecord::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'status' => 'active',
        ]);

        return new StaffId($record->id);
    }

    public function updateProfile(StaffId $id, string $name, string $phone, string $email): void
    {
        StaffRecord::query()->findOrFail($id->value)->update([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    public function save(StaffAccount $staff): void
    {
        StaffRecord::query()
            ->whereKey($staff->id()->value)
            ->update(['status' => $staff->status()]);
    }

    private function toDomain(StaffRecord $record): StaffAccount
    {
        return StaffAccount::reconstitute(
            id: new StaffId($record->id),
            name: $record->name,
            phone: $record->phone,
            email: $record->email,
            status: $record->status,
        );
    }
}
