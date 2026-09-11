<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Laravel;

use Domain\RBAC\Domain\Repositories\RoleRepository;
use Domain\RBAC\Domain\Repositories\StaffRoleAssignmentRepository;
use Domain\RBAC\Domain\Repositories\StaffShopGrantRepository;
use Domain\RBAC\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use Domain\RBAC\Infrastructure\Persistence\Repositories\EloquentStaffRoleAssignmentRepository;
use Domain\RBAC\Infrastructure\Persistence\Repositories\EloquentStaffShopGrantRepository;
use Illuminate\Support\ServiceProvider;

final class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StaffShopGrantRepository::class, EloquentStaffShopGrantRepository::class);
        $this->app->bind(StaffRoleAssignmentRepository::class, EloquentStaffRoleAssignmentRepository::class);
        $this->app->bind(RoleRepository::class, EloquentRoleRepository::class);
    }
}
