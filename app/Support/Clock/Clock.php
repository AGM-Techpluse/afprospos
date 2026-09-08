<?php

declare(strict_types=1);

namespace App\Support\Clock;

use Carbon\CarbonImmutable;

/**
 * Domain/Application code that needs "now" depends on this contract
 * instead of calling now()/Carbon::now() directly, so time can be frozen
 * in tests (bind a FrozenClock in tests instead of relying on
 * Carbon::setTestNow(), which is easy to forget to reset).
 */
interface Clock
{
    public function now(): CarbonImmutable;
}
