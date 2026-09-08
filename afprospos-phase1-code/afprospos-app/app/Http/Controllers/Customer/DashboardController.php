<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('Customer/Dashboard');
    }
}
