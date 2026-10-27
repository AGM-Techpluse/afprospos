<?php

declare(strict_types=1);

namespace Domain\Finance\Infrastructure\Laravel;

use Domain\Finance\Domain\Repositories\ExpenseRepository;
use Domain\Finance\Infrastructure\Persistence\Repositories\EloquentExpenseRepository;
use Illuminate\Support\ServiceProvider;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExpenseRepository::class, EloquentExpenseRepository::class);
    }
}
