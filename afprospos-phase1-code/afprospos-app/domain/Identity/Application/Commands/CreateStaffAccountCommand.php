<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

/**
 * Deliberately carries optional initial role/shop assignment so that
 * "create a staff member" is completable as a single Controller
 * action -> single Command (CPNC §2.4), rather than the Controller
 * chaining three separate Handler calls itself. The Handler orchestrates
 * Identity's own write plus RBAC's assignment writes inside one Atomic
 * transaction — see CreateStaffAccountHandler for the cross-module
 * boundary rationale.
 *
 * @param  string[]  $initialRoleNames
 * @param  int[]  $initialShopIds
 */
final readonly class CreateStaffAccountCommand
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public string $password,
        public array $initialRoleNames,
        public array $initialShopIds,
        public int $createdByStaffId,
    ) {}
}
