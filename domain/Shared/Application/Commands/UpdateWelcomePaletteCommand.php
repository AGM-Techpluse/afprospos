<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Commands;

/** `palette` null resets the landing page to its built-in design. */
final readonly class UpdateWelcomePaletteCommand
{
    /** @param  array<string, string>|null  $palette */
    public function __construct(
        public ?array $palette,
        public int $updatedByStaffId,
    ) {}
}
