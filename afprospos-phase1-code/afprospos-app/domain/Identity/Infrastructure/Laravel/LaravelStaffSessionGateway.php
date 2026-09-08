<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class LaravelStaffSessionGateway implements StaffSessionGateway
{
    public function login(StaffId $id, bool $remember): void
    {
        $record = StaffRecord::query()->findOrFail($id->value);

        Auth::guard('staff')->login($record, $remember);
    }

    public function logout(): void
    {
        Auth::guard('staff')->logout();
    }

    public function forceLogoutAllSessions(StaffId $id): void
    {
        // Immediate effect only with SESSION_DRIVER=database (Laravel's
        // sessions table carries a user_id column populated by the guard
        // on login). On any other driver this is a harmless no-op —
        // EnsureStaffIsActive middleware still blocks the account on its
        // very next request regardless of session driver.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $id->value)
                ->delete();
        }
    }
}
