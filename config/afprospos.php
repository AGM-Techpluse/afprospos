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
    | Checkout reservation window
    |--------------------------------------------------------------------
    |
    | How long a POS checkout holds its inventory reservation before the
    | expiry worker releases it (BLD §4.4: independently configurable
    | from repair down-payment deadlines, which use a separate,
    | longer-scale timer).
    |
    */
    'checkout_reservation_minutes' => 15,

    /*
    |--------------------------------------------------------------------
    | Return / refund window
    |--------------------------------------------------------------------
    |
    | WAR-BR-12: how many days after a sale a customer may request a
    | return/refund before it's denied by default (subject to an
    | authorized administrative override).
    |
    */
    'return_window_days' => 14,

    /*
    |--------------------------------------------------------------------
    | Bank transfer instructions
    |--------------------------------------------------------------------
    |
    | Shown to a customer on the payment page when they choose "Bank
    | transfer" (Customer\PaymentController) — placeholder values; the
    | shop owner should replace these with real account details before
    | this flow goes live.
    |
    */
    'bank_transfer_instructions' => [
        'account_name' => 'AfProsPos Limited',
        'account_number' => '0000000000',
        'bank_name' => 'Update this in config/afprospos.php',
    ],

    /*
    |--------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------
    |
    | NOTIF-BR-06: max delivery attempts and the increasing retry
    | intervals (seconds) between them. NOTIF-BR-16: categories that
    | ignore a customer's channel preference — always sent regardless of
    | notification_preferences. Provider credentials (RESEND_API_KEY,
    | MAIL_*) stay in .env per ADD §21A — never referenced here.
    |
    */
    /*
    |--------------------------------------------------------------------
    | Site appearance defaults
    |--------------------------------------------------------------------
    |
    | The landing page's colour tokens and their original design values.
    | An admin's saved palette (Settings → Site appearance) overrides these.
    | Must match the tokens in resources/js/lib/welcomePalette.ts.
    |
    */
    'appearance' => [
        'welcome_palette' => [
            'page_start' => '#CFE3FF',
            'page_mid' => '#EAF3FF',
            'page_end' => '#FFFFFF',
            'text' => '#1B1F27',
            'text_muted' => '#636B78',
            'accent' => '#2F6FED',
            'surface' => '#FFFFFF',
            'on_accent' => '#FFFFFF',
            'panel' => '#E8EDF3',
            'border' => '#EEF0F3',
            'navy' => '#10192E',
            'lime' => '#A0ED25',
            'on_lime' => '#14200A',
            'ink' => '#000000',
        ],
    ],

    'notifications' => [
        'max_delivery_attempts' => 3,
        'retry_backoff_seconds' => [60, 300, 900],
        'mandatory_categories' => ['transactional', 'security'],
    ],

    /*
    |--------------------------------------------------------------------
    | Repair authorization window
    |--------------------------------------------------------------------
    |
    | down_payment_deadline_at doubles as the authorization-response
    | deadline for a zero-down-payment repair too (Phase 6 scope
    | decision: one deadline concept, not two) — this is the
    | independently configurable, longer-scale timer BLD §4.4 contrasts
    | with checkout_reservation_minutes above.
    |
    */
    'repairs' => [
        'authorization_response_hours' => 48,
    ],

    /*
    |--------------------------------------------------------------------
    | Collection deadlines and storage fees
    |--------------------------------------------------------------------
    |
    | A flat daily storage-fee rate, snapshotted onto each collection
    | case at creation time so a later config change never retroactively
    | alters an already-open case (DBDD §12.1's storage_fee_policy_snapshot).
    | No proration, no tiers — a configuration decision the business will
    | refine (BLD §18), not a business-logic gap.
    |
    */
    'collection' => [
        'default_deadline_days' => 14,
        'abandonment_threshold_days' => 30,
        'storage_fee_minor_per_day' => 0,
        'grace_days' => 3,
    ],

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
        'repairs' => ['view', 'create', 'edit', 'progress', 'assign', 'manage', 'approve-payment', 'authorize', 'transfer'],
        'sales' => ['view', 'create', 'cancel', 'approve-discount'],
        'commission' => ['view', 'manage'],
        'inventory' => ['view', 'create', 'edit', 'delete'],
        'referrals' => ['view', 'manage'],
        'marketing' => ['view', 'manage'],
        'expenses' => ['view', 'create', 'manage'],
        'reports' => ['view'],
        'payments' => ['view', 'confirm', 'reject', 'dispute', 'refund'],
        'collection' => ['view', 'process', 'override', 'transfer'],
        'warranty' => ['view', 'create', 'assess', 'resolve', 'approve-refund', 'process-exchange', 'manage'],
        'audit' => ['view'],
        'settings' => ['view', 'edit'],
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
        // Per-shop manager: reviews technician findings (reported back
        // off-system — technicians don't use this software), drives every
        // repair_status/diagnosis/down-payment decision, and is the
        // required second approver for both the down payment and the
        // final settlement frontdesk collects. Deliberately excludes
        // 'repairs.create'/'sales.create' — intake and cash handling stay
        // with frontdesk, so a Manager approves rather than self-serves.
        'Manager' => [
            'repairs' => ['view', 'progress', 'approve-payment', 'authorize', 'transfer'],
            'collection' => ['view', 'transfer'],
            'sales' => ['view', 'approve-discount'],
        ],
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
            'payments' => 'full',
            'collection' => ['view', 'override'],
            'warranty' => ['view', 'approve-refund', 'process-exchange'],
        ],
        'Technician' => [
            // Confirmed with the stakeholder: technicians don't use this
            // software at all — they test/diagnose/repair physically and
            // report back to the Manager (or Shop Owner) by whatever means,
            // who enters every finding and drives every status change on
            // their behalf. 'view' stays in case that changes later (e.g. a
            // workshop tablet), but there is currently no write action a
            // Technician needs.
            'repairs' => ['view'],
            'inventory' => ['view'],
            'reports' => ['view'],
            'collection' => ['view', 'process'],
            'warranty' => ['view', 'assess', 'resolve'],
        ],
        'Cashier' => [
            'sales' => ['view', 'create', 'cancel'],
            // Frontdesk creates the repair at intake and is the one who
            // actually touches the customer's money — it records that a
            // down payment or final payment was received ('edit' covers
            // the *request* actions) but a Manager/Shop Owner
            // ('repairs.approve-payment') always has to confirm both
            // before the repair proceeds or the money counts as settled.
            // Everything in the repair flow except what needs an approval (authorization,
            // quote approval, payment decisions) — those stay with the Manager.
            'repairs' => ['view', 'create', 'edit', 'progress', 'assign', 'transfer'],
            'collection' => ['view', 'process', 'transfer'],
            'inventory' => ['view'],
            'referrals' => ['view'],
            'reports' => ['view'],
            'warranty' => ['view', 'create'],
        ],
        'Product Staff' => [
            'inventory' => 'full',
            'repairs' => ['view'],
            'sales' => ['view'],
            'reports' => ['view'],
            'collection' => ['view'],
            'warranty' => ['view'],
        ],
        'Marketing Staff' => [
            'marketing' => 'full',
            'referrals' => 'full',
            'reports' => ['view'],
        ],
    ],
];
