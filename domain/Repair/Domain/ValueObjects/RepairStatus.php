<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\ValueObjects;

use InvalidArgumentException;

/** Mirrors `repair_jobs.repair_status`'s enum exactly (DBDD §11.1). */
final readonly class RepairStatus
{
    private const VALID = [
        'received',
        'diagnosing',
        'awaiting_authorization',
        'payment_overdue',
        'expired_cancelled',
        'awaiting_parts',
        'in_progress',
        'completed',
        'unrepairable',
        'failed_requires_resolution',
    ];

    public function __construct(public string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid repair status [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
