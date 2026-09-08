<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\CreateStaffRequest;
use App\Http\Requests\Admin\GrantShopAccessRequest;
use Domain\Identity\Application\Commands\CreateStaffAccountCommand;
use Domain\Identity\Application\Commands\DeactivateStaffAccountCommand;
use Domain\Identity\Application\Handlers\CreateStaffAccountHandler;
use Domain\Identity\Application\Handlers\DeactivateStaffAccountHandler;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\RBAC\Application\Commands\AssignRoleToStaffCommand;
use Domain\RBAC\Application\Commands\GrantShopAccessCommand;
use Domain\RBAC\Application\Commands\RevokeRoleFromStaffCommand;
use Domain\RBAC\Application\Commands\RevokeShopAccessCommand;
use Domain\RBAC\Application\Handlers\AssignRoleToStaffHandler;
use Domain\RBAC\Application\Handlers\GrantShopAccessHandler;
use Domain\RBAC\Application\Handlers\RevokeRoleFromStaffHandler;
use Domain\RBAC\Application\Handlers\RevokeShopAccessHandler;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every action below is intentionally one Controller method -> one
 * Command -> one Handler (CPNC §2.4). Cross-cutting orchestration (e.g.
 * "create staff AND assign its first role") lives inside
 * CreateStaffAccountHandler, not stitched together here.
 */
final class StaffController
{
    public function __construct(
        private readonly StaffDirectoryQuery $staffDirectory,
        private readonly CreateStaffAccountHandler $createStaff,
        private readonly DeactivateStaffAccountHandler $deactivateStaff,
        private readonly AssignRoleToStaffHandler $assignRole,
        private readonly RevokeRoleFromStaffHandler $revokeRole,
        private readonly GrantShopAccessHandler $grantShopAccess,
        private readonly RevokeShopAccessHandler $revokeShopAccess,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Staff/Index', [
            'staff' => $this->staffDirectory->all(),
        ]);
    }

    public function store(CreateStaffRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createStaff->handle(new CreateStaffAccountCommand(
            name: $request->string('name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            initialRoleNames: $request->array('initial_role_names') ?? [],
            initialShopIds: array_map('intval', $request->array('initial_shop_ids') ?? []),
            createdByStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.staff.index');
    }

    public function assignRole(AssignRoleRequest $request, int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->assignRole->handle(new AssignRoleToStaffCommand(
            staffId: $staff,
            roleName: $request->string('role_name')->toString(),
            assignedByStaffId: $actor->staffId->value,
        ));

        return back();
    }

    public function revokeRole(AssignRoleRequest $request, int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->revokeRole->handle(new RevokeRoleFromStaffCommand(
            staffId: $staff,
            roleName: $request->string('role_name')->toString(),
            revokedByStaffId: $actor->staffId->value,
        ));

        return back();
    }

    public function grantShopAccess(GrantShopAccessRequest $request, int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->grantShopAccess->handle(new GrantShopAccessCommand(
            staffId: $staff,
            shopId: $request->integer('shop_id'),
            grantedByStaffId: $actor->staffId->value,
        ));

        return back();
    }

    public function revokeShopAccess(GrantShopAccessRequest $request, int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->revokeShopAccess->handle(new RevokeShopAccessCommand(
            staffId: $staff,
            shopId: $request->integer('shop_id'),
            revokedByStaffId: $actor->staffId->value,
        ));

        return back();
    }

    public function deactivate(int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deactivateStaff->handle(new DeactivateStaffAccountCommand(
            staffId: $staff,
            deactivatedByStaffId: $actor->staffId->value,
            reason: null,
        ));

        return redirect()->route('admin.staff.index');
    }
}
