<?php

declare(strict_types=1);

namespace App\Support\Transactions;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Makes transaction boundaries an explicit, visible dependency in
 * Application Handlers instead of a bare DB::transaction() call scattered
 * ad hoc (ADD §30). Retries a bounded number of times so a transient
 * MySQL deadlock/serialization failure is retried automatically — a
 * genuine business exception (e.g. InsufficientAvailableStock) is NOT
 * retried, because Laravel's transaction() only retries on a detected
 * deadlock/lock-wait-timeout, not on an arbitrary thrown exception
 * (ADD §30.2).
 */
final class Atomic
{
    private const DEFAULT_ATTEMPTS = 3;

    public function run(Closure $operation, int $attempts = self::DEFAULT_ATTEMPTS): mixed
    {
        return DB::transaction($operation, $attempts);
    }
}
