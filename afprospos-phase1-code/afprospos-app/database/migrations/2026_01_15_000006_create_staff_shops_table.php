<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC-owned table implementing BRD RBAC-05 (shop-scoped access grants)
 * and BLD RBAC-BR-09 (assignment history maintained).
 *
 * This is a grant, not a role: it answers "which shops can this staff
 * member operate in", independent of "what can they do there" (roles/
 * permissions, owned by spatie/laravel-permission's tables).
 *
 * Deliberately NOT hard-deleted on revoke (DBDD §7 Delete Policy) — a
 * revoked grant is recorded via `revoked_at`, never removed, so historical
 * "who had access to shop X on date Y" questions remain answerable.
 * Re-granting after a revoke inserts a new row rather than reviving the
 * old one, so the full grant/revoke timeline is preserved.
 *
 * Uniqueness of "one active grant per (staff, shop)" is enforced at the
 * application layer (EloquentStaffShopGrantRepository), not by a DB
 * constraint, because MySQL 8 does not support partial/filtered unique
 * indexes (DBDD §37 — application owns business-rule integrity).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_shops', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            // Shared Kernel FKs — valid database relationships (DBDD §4.1.A).
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('granted_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['staff_id', 'shop_id'], 'staff_shops_staff_shop_idx');
            $table->index(['shop_id', 'revoked_at'], 'staff_shops_shop_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_shops');
    }
};
