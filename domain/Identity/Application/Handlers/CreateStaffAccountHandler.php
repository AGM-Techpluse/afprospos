<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\CreateStaffAccountCommand;
use Domain\Identity\Domain\Exceptions\DuplicateStaffEmail;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\RBAC\Application\Commands\AssignRoleToStaffCommand;
use Domain\RBAC\Application\Commands\GrantShopAccessCommand;
use Domain\RBAC\Application\Handlers\AssignRoleToStaffHandler;
use Domain\RBAC\Application\Handlers\GrantShopAccessHandler;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Cross-module orchestration, by design (ADD §4.1 "explicit contracts
 * owned by another module when the integration requires one"):
 *
 * Identity owns "does this person exist and can they authenticate".
 * RBAC owns "what can they do and where". Creating a staff member from
 * one admin form naturally produces both facts at once, so this Handler
 * — not the Controller (CPNC §2.4: one controller action, one command) —
 * orchestrates both modules' Application Handlers inside a single Atomic
 * transaction. It depends on RBAC's Application Commands/Handlers (its
 * published orchestration entrypoints), never on RBAC's Domain internals
 * or Eloquent models. If this coupling ever becomes a problem, the
 * extraction point is exactly here: replace the direct Handler
 * dependency with a queued domain event (StaffAccountCreated) that RBAC
 * listens for — but that is unnecessary complexity for Phase 1's actual
 * requirement.
 */
final class CreateStaffAccountHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly AssignRoleToStaffHandler $assignRole,
        private readonly GrantShopAccessHandler $grantShopAccess,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateStaffAccountCommand $command): StaffId
    {
        if ($this->staff->existsWithEmail($command->email)) {
            throw DuplicateStaffEmail::forEmail($command->email);
        }

        return $this->atomic->run(function () use ($command): StaffId {
            $staffId = $this->staff->create(
                $command->name,
                $command->phone,
                $command->email,
                $command->password,
            );

            foreach ($command->initialRoleNames as $roleName) {
                $this->assignRole->handle(new AssignRoleToStaffCommand(
                    staffId: $staffId->value,
                    roleName: $roleName,
                    assignedByStaffId: $command->createdByStaffId,
                ));
            }

            foreach ($command->initialShopIds as $shopId) {
                $this->grantShopAccess->handle(new GrantShopAccessCommand(
                    staffId: $staffId->value,
                    shopId: $shopId,
                    grantedByStaffId: $command->createdByStaffId,
                ));
            }

            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffAccountCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: null,
                afterState: [
                    'name' => $command->name,
                    'email' => $command->email,
                    'initial_roles' => $command->initialRoleNames,
                    'initial_shop_ids' => $command->initialShopIds,
                ],
            );

            return $staffId;
        });
    }
}
