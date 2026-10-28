<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Persistence\Repositories;

use Domain\Identity\Domain\Entities\StaffAccount;
use Domain\Identity\Domain\Exceptions\StaffHasExistingRecords;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\QueryException;
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

    public function updatePassword(StaffId $id, string $plainPassword): void
    {
        StaffRecord::query()->findOrFail($id->value)->update([
            'password' => Hash::make($plainPassword),
        ]);
    }

    public function save(StaffAccount $staff): void
    {
        StaffRecord::query()
            ->whereKey($staff->id()->value)
            ->update(['status' => $staff->status()]);
    }

    /**
     * Role/shop-grant history (staff_shops, repairs, sales, payments,
     * commissions, etc.) is protected by real `restrictOnDelete` foreign
     * keys (DBDD §7) — a FK violation here means this account has existing
     * business records, translated to a domain error so the caller can
     * suggest deactivation instead. Role pivot rows (model_has_roles) are
     * NOT FK-protected (they are polymorphic), so they are cleared
     * explicitly to avoid leaving orphaned rows behind.
     */
    public function delete(StaffId $id): void
    {
        $record = StaffRecord::query()->findOrFail($id->value);

        $record->syncRoles([]);
        $record->syncPermissions([]);

        try {
            $record->delete();
        } catch (QueryException $exception) {
            // SQLSTATE 23000 (integrity constraint violation) is the
            // portable signal across both MySQL (errno 1451) and the
            // SQLite driver the test suite runs on (errno 19) — a
            // FK-specific errno check would only work on one of them.
            if (($exception->errorInfo[0] ?? null) === '23000') {
                throw StaffHasExistingRecords::forStaff($id);
            }

            throw $exception;
        }
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
