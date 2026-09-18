<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\ValueObjects;

final readonly class NotificationEventId
{
    public function __construct(public int $value) {}

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
