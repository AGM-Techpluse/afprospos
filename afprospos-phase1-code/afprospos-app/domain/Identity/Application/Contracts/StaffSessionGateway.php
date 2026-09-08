<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Contracts;

use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Owned by Identity's Application layer (Dependency Inversion — CPNC
 * §4.4), implemented in Infrastructure against Laravel's `staff` auth
 * guard. Kept separate from StaffRepository (which stays framework-free)
 * so establishing/destroying an HTTP session — an inherently
 * framework-bound concern — never forces Illuminate imports into the
 * Domain layer.
 */
interface StaffSessionGateway
{
    public function login(StaffId $id, bool $remember): void;

    public function logout(): void;

    /**
     * Best-effort immediate invalidation of every other active session
     * belonging to this staff member (e.g. on deactivation). Requires the
     * `database` session driver to take effect immediately; regardless of
     * driver, EnsureStaffIsActive middleware guarantees the account loses
     * access on its very next request either way.
     */
    public function forceLogoutAllSessions(StaffId $id): void;
}
