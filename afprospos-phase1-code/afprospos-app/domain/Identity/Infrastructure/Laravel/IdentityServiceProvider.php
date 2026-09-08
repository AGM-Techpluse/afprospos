<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Contracts\CustomerSessionGateway;
use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Identity\Domain\Repositories\CustomerRepository;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Identity\Infrastructure\Persistence\Repositories\EloquentCustomerRepository;
use Domain\Identity\Infrastructure\Persistence\Repositories\EloquentStaffRepository;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StaffRepository::class, EloquentStaffRepository::class);
        $this->app->bind(CustomerRepository::class, EloquentCustomerRepository::class);
        $this->app->bind(StaffSessionGateway::class, LaravelStaffSessionGateway::class);
        $this->app->bind(CustomerSessionGateway::class, LaravelCustomerSessionGateway::class);
    }
}
