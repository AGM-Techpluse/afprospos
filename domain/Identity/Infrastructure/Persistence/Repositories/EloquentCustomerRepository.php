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
    public function findByPhone(string $phone): ?CustomerAccount
    {
        $record = CustomerRecord::query()->where('phone', $phone)->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function existsWithPhone(string $phone): bool
    {
        return CustomerRecord::query()->where('phone', $phone)->exists();
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
