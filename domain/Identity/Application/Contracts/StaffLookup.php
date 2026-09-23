<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Contracts;

/**
 * The published cross-module read contract for looking up staff by id or
 * by role — Notifications resolves a staff recipient's contact details
 * through this (never `StaffRecord` directly), and a Listener that needs
 * "every Shop Owner" (e.g. to route an approval request) resolves them
 * through `findByRole()` rather than duplicating RBAC's role lookup.
 */
interface StaffLookup
{
    /**
     * @return array{id: int, name: string, email: ?string}|null
     */
    public function find(int $id): ?array;

    /**
     * Every active staff member holding the given role — used to resolve
     * "the Shop Owner(s)" as a notification recipient list.
     *
     * @return array<int, array{id: int, name: string, email: ?string}>
     */
    public function findByRole(string $role): array;

    /**
     * Every active staff member holding the given permission, however
     * they got it (Shop Owner's blanket grant, a per-shop Manager role,
     * or any other role the business assigns it to) — used to resolve an
     * approval notification's recipient list without hard-coding a role
     * name (e.g. `repairs.approve-payment` reaches Shop Owner and Manager
     * alike, not just whichever role happened to be the owner's).
     *
     * @return array<int, array{id: int, name: string, email: ?string}>
     */
    public function findByPermission(string $permission): array;
}
