<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Commands;

/** `accent` null resets the admin UI to its original blue. */
final readonly class UpdateAdminThemeCommand
{
    public function __construct(
        public ?string $accent,
        public int $updatedByStaffId,
    ) {}
}
