<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Persistence\Repositories;

use Domain\Identity\Domain\Entities\CustomerAccount;
use Domain\Identity\Domain\Repositories\CustomerRepository;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Support\Facades\Hash;

final class EloquentCustomerRepository implements CustomerRepository
{
    public function findById(CustomerId $id): ?CustomerAccount
    {
        $record = CustomerRecord::query()->find($id->value);

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findByPhone(string $phone): ?CustomerAccount
    {
        $record = CustomerRecord::query()->where('phone', $phone)->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function existsWithPhone(string $phone): bool
    {
        return CustomerRecord::query()->where('phone', $phone)->exists();
    }

    public function findByEmail(string $email): ?CustomerAccount
    {
        $record = CustomerRecord::query()->where('email', $email)->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function existsWithEmail(string $email): bool
    {
        return CustomerRecord::query()->where('email', $email)->exists();
    }

    public function existsWithPhoneExcept(string $phone, CustomerId $exceptId): bool
    {
        return CustomerRecord::query()
            ->where('phone', $phone)
            ->where('id', '!=', $exceptId->value)
            ->exists();
    }

    public function existsWithEmailExcept(string $email, CustomerId $exceptId): bool
    {
        return CustomerRecord::query()
            ->where('email', $email)
            ->where('id', '!=', $exceptId->value)
            ->exists();
    }

    public function updateProfile(CustomerId $id, string $name, string $phone, ?string $email): void
    {
        CustomerRecord::query()->findOrFail($id->value)->update([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    public function verifyPassword(CustomerId $id, string $plainPassword): bool
    {
        $record = CustomerRecord::query()->findOrFail($id->value);

        return Hash::check($plainPassword, $record->password);
    }

    public function create(string $name, string $phone, ?string $email, string $plainPassword): CustomerId
    {
        $record = CustomerRecord::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'notification_preferences' => ['whatsapp' => true, 'email' => true, 'in_app' => true],
            'marketing_opt_out' => false,
            'status' => 'active',
        ]);

        return new CustomerId($record->id);
    }

    private function toDomain(CustomerRecord $record): CustomerAccount
    {
        return CustomerAccount::reconstitute(
            id: new CustomerId($record->id),
            name: $record->name,
            phone: $record->phone,
            email: $record->email,
            status: $record->status,
        );
    }
}
