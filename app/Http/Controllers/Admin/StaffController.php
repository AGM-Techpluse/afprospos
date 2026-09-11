<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\CreateStaffRequest;
use App\Http\Requests\Admin\GrantShopAccessRequest;
use App\Http\Requests\Admin\UpdateStaffProfileRequest;
use Domain\Identity\Application\Commands\CreateStaffAccountCommand;
use Domain\Identity\Application\Commands\DeactivateStaffAccountCommand;
use Domain\Identity\Application\Commands\UpdateStaffProfileCommand;
use Domain\Identity\Application\Handlers\CreateStaffAccountHandler;
use Domain\Identity\Application\Handlers\DeactivateStaffAccountHandler;
use Domain\Identity\Application\Handlers\UpdateStaffProfileHandler;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Identity\Domain\Exceptions\DuplicateStaffEmail;
use Domain\RBAC\Application\Commands\AssignRoleToStaffCommand;
use Domain\RBAC\Application\Commands\GrantShopAccessCommand;
use Domain\RBAC\Application\Commands\RevokeRoleFromStaffCommand;
use Domain\RBAC\Application\Commands\RevokeShopAccessCommand;
use Domain\RBAC\Application\Handlers\AssignRoleToStaffHandler;
use Domain\RBAC\Application\Handlers\GrantShopAccessHandler;
use Domain\RBAC\Application\Handlers\RevokeRoleFromStaffHandler;
use Domain\RBAC\Application\Handlers\RevokeShopAccessHandler;
use Domain\RBAC\Application\Queries\AvailableRolesQuery;
use Domain\RBAC\Application\Queries\StaffShopGrantsQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        private readonly StaffShopGrantsQuery $staffShopGrants,
        private readonly AvailableRolesQuery $availableRoles,
        private readonly ShopDirectoryQuery $shopDirectory,
        private readonly CreateStaffAccountHandler $createStaff,
        private readonly UpdateStaffProfileHandler $updateStaffProfile,
        private readonly DeactivateStaffAccountHandler $deactivateStaff,
        private readonly AssignRoleToStaffHandler $assignRole,
        private readonly RevokeRoleFromStaffHandler $revokeRole,
        private readonly GrantShopAccessHandler $grantShopAccess,
        private readonly RevokeShopAccessHandler $revokeShopAccess,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Staff/Index', [
            'staff' => $this->staffDirectory->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
                role: $request->string('role')->toString() ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'role' => $request->string('role')->toString(),
            ],
            'availableRoles' => $this->availableRoles->names(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Staff/Create', [
            'availableRoles' => $this->availableRoles->names(),
            'shops' => $this->shopDirectory->all(),
        ]);
    }

    public function show(int $staff): Response
    {
        $staffMember = $this->staffDirectory->find($staff);

        abort_if($staffMember === null, 404);

        return Inertia::render('Admin/Staff/Show', [
            'staffMember' => $staffMember,
            'grantedShopIds' => $this->staffShopGrants->activeShopIdsForStaff($staff),
            'availableRoles' => $this->availableRoles->names(),
            'shops' => $this->shopDirectory->all(),
        ]);
    }

    public function edit(int $staff): Response
    {
        $staffMember = $this->staffDirectory->find($staff);

        abort_if($staffMember === null, 404);

        return Inertia::render('Admin/Staff/Edit', [
            'staffMember' => $staffMember,
        ]);
    }

    public function update(UpdateStaffProfileRequest $request, int $staff): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->updateStaffProfile->handle(new UpdateStaffProfileCommand(
                staffId: $staff,
                name: $request->string('name')->toString(),
                phone: $request->string('phone')->toString(),
                email: $request->string('email')->toString(),
                updatedByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateStaffEmail $exception) {
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        return redirect()->route('admin.staff.show', $staff);
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
