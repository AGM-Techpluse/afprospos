<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure;

use Carbon\CarbonImmutable;
use Domain\Shared\Domain\Contracts\Clock;

/**
 * Production clock using CarbonImmutable::now().
 */
final class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
