<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(): Response
    {
        // Phase 1 exit criteria is server-enforced access, not a built
        // dashboard — this page exists so persona routing/navigation is
        // provably reachable end-to-end. Phase 2 replaces the body with
        // the real shared-design-system shell.
        return Inertia::render('Admin/Dashboard/Index');
    }
}
