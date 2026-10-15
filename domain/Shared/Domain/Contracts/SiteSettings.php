<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Contracts;

/** Platform-wide settings an admin can change at runtime (site appearance, etc.). Values are JSON-serialisable. */
interface SiteSettings
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, int $updatedByStaffId): void;

    public function forget(string $key): void;
}
