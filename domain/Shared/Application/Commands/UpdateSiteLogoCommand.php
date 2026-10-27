<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Commands;

/** `logoPath` null resets to the built-in default logo asset. */
final readonly class UpdateSiteLogoCommand
{
    public function __construct(
        public ?string $logoPath,
        public int $updatedByStaffId,
    ) {}
}
