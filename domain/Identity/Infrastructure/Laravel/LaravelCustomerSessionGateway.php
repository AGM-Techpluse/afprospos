<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Contracts\CustomerSessionGateway;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Support\Facades\Auth;

final class LaravelCustomerSessionGateway implements CustomerSessionGateway
{
    public function login(CustomerId $id, bool $remember): void
    {
        $record = CustomerRecord::query()->findOrFail($id->value);

        Auth::guard('customer')->login($record, $remember);
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();
    }
}
