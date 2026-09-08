<?php

declare(strict_types=1);

/**
 * Application-wide, non-secret AfProsPos configuration. Values here are
 * business/architecture constants referenced by name across modules so
 * "Shop Owner" (for example) is never a magic string duplicated in five
 * different files.
 */
return [

    /*
    |--------------------------------------------------------------------
    | Owner role name
    |--------------------------------------------------------------------
    |
    | The one role that bypasses shop-scope restriction entirely
    | (BLD "Shop Owner may operate across all shops"). Referenced via
    | config() rather than a literal string in every module that needs
    | to check "is this actor the owner".
    |
    */
    'owner_role_name' => 'Shop Owner',

    /*
    |--------------------------------------------------------------------
    | RBAC permission catalog
    |--------------------------------------------------------------------
    |
    | Every permission string the system recognises, grouped by BRD §5's
    | module list, seeded by database/seeders/RolePermissionSeeder.php.
    | Modules not yet built in this phase (repairs, sales, inventory, ...)
    | are seeded now anyway so Phase 3+ work consumes an already-agreed
    | permission catalog instead of inventing one per module as it lands.
    |
    | Action sets follow BRD RBAC-04 (view, create, edit, delete, approve)
    | narrowed to what is meaningful per module.
    |
    */
    'permissions' => [
        'staff' => ['view', 'create', 'edit', 'deactivate', 'assign'],
        'shops' => ['view', 'create', 'edit'],
        'repairs' => ['view', 'create', 'edit', 'approve'],
        'sales' => ['view', 'create'],
        'commission' => ['view', 'manage'],
        'inventory' => ['view', 'create', 'edit', 'delete'],
        'referrals' => ['view', 'manage'],
        'marketing' => ['view', 'manage'],
        'expenses' => ['view', 'create', 'manage'],
        'reports' => ['view'],
    ],

    /*
    |--------------------------------------------------------------------
    | Role -> permission-module starting configuration
    |--------------------------------------------------------------------
    |
    | Transcribes the BRD §5 RBAC Permission Matrix into a seedable
    | starting point. 'full' means every action for that module; a
    | listed action array means only those actions. This is a
    | configuration decision the business will refine (BLD §18:
    | "configuration decisions to make within the rules above, not
    | business-logic gaps") — not a hard-coded final policy.
    |
    */
    'role_starting_permissions' => [
        'Shop Owner' => ['*' => 'full'],
        'Accountant' => [
            'shops' => ['view'],
            'repairs' => ['view'],
            'sales' => ['view'],
            'commission' => 'full',
            'inventory' => ['view'],
            'referrals' => ['view'],
            'marketing' => ['view'],
            'expenses' => 'full',
            'reports' => 'full',
        ],
        'Technician' => [
            'repairs' => 'full',
            'inventory' => ['view'],
            'reports' => ['view'],
        ],
        'Cashier' => [
            'sales' => 'full',
            'inventory' => ['view'],
            'referrals' => ['view'],
            'reports' => ['view'],
        ],
        'Product Staff' => [
            'inventory' => 'full',
            'repairs' => ['view'],
            'sales' => ['view'],
            'reports' => ['view'],
        ],
        'Marketing Staff' => [
            'marketing' => 'full',
            'referrals' => 'full',
            'reports' => ['view'],
        ],
    ],
];
