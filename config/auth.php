<?php

declare(strict_types=1);

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/**
 * Replaces the starter kit's generic single-guard `web`/`users` setup
 * (Phase 0 Setup Guide §6.2 / §9.7: "do not build AfProsPos business
 * behavior on its generated App\Models\User"). AfProsPos has two,
 * structurally distinct identities — staff (RBAC-governed, Admin
 * Dashboard) and customers (Customer Dashboard, no roles) — so it needs
 * two guards and two user providers, never one shared `users` table.
 *
 * DELETE app/Models/User.php and its migration/factory in the same
 * change that applies this file (CPNC Appendix A #10 — no orphaned
 * files left behind a refactor). See the Phase 1 guide, Step 9.
 */
return [

    'defaults' => [
        'guard' => 'staff',
        'passwords' => 'staff',
    ],

    'guards' => [
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff',
        ],

        'customer' => [
            'driver' => 'session',
            'provider' => 'customer',
        ],
    ],

    'providers' => [
        'staff' => [
            'driver' => 'eloquent',
            'model' => StaffRecord::class,
        ],

        'customer' => [
            'driver' => 'eloquent',
            'model' => CustomerRecord::class,
        ],
    ],

    'passwords' => [
        'staff' => [
            'provider' => 'staff',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'customer' => [
            'provider' => 'customer',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
