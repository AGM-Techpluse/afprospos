<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateRoleRequest;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use Domain\RBAC\Application\Commands\CreateRoleCommand;
use Domain\RBAC\Application\Commands\UpdateRolePermissionsCommand;
use Domain\RBAC\Application\Handlers\CreateRoleHandler;
use Domain\RBAC\Application\Handlers\UpdateRolePermissionsHandler;
use Domain\RBAC\Application\Queries\AvailableRolesQuery;
use Domain\RBAC\Domain\Exceptions\DuplicateRoleName;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class RoleController
{
    public function __construct(
        private readonly AvailableRolesQuery $availableRoles,
        private readonly CreateRoleHandler $createRole,
        private readonly UpdateRolePermissionsHandler $updateRolePermissions,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Staff/Roles/Index', [
            'roles' => $this->availableRoles->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Staff/Roles/Create', [
            'permissionCatalog' => $this->availableRoles->catalog(),
        ]);
    }

    public function store(CreateRoleRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->createRole->handle(new CreateRoleCommand(
                name: $request->string('name')->toString(),
                permissionNames: $request->array('permissions') ?? [],
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateRoleName $exception) {
            throw ValidationException::withMessages(['name' => $exception->getMessage()]);
        }

        return redirect()->route('admin.staff.roles.index');
    }

    public function edit(string $role): Response
    {
        $roleDetail = $this->availableRoles->find($role);

        abort_if($roleDetail === null, 404);

        return Inertia::render('Admin/Staff/Roles/Edit', [
            'role' => $roleDetail,
            'permissionCatalog' => $this->availableRoles->catalog(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, string $role): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateRolePermissions->handle(new UpdateRolePermissionsCommand(
            name: $role,
            permissionNames: $request->array('permissions') ?? [],
            updatedByStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.staff.roles.index');
    }
}
