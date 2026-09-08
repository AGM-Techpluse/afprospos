<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Contracts;

use Carbon\CarbonImmutable;

/**
 * Clock abstraction for testable time.
 *
 * Domain code uses this contract instead of calling Carbon::now() directly,
 * allowing deterministic time control in tests.
 */
interface Clock
{
    public function now(): CarbonImmutable;
}
