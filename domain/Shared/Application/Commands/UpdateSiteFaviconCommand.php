<?php

declare(strict_types=1);

namespace Domain\Shared\Application\Commands;

/** `faviconPath` null resets to the built-in default favicon asset. */
final readonly class UpdateSiteFaviconCommand
{
    public function __construct(
        public ?string $faviconPath,
        public int $updatedByStaffId,
    ) {}
}
