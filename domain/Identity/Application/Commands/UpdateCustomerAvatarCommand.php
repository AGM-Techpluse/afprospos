<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

use Domain\Shared\Domain\ValueObjects\CustomerId;

/** `avatarPath` is already-stored (Controller-level file handling, CPNC §2.3) — null removes a previously-set avatar back to the default initial-letter avatar. */
final readonly class UpdateCustomerAvatarCommand
{
    public function __construct(
        public CustomerId $customerId,
        public ?string $avatarPath,
    ) {}
}
