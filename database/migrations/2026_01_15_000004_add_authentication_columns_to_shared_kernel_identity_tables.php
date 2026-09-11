<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Identity module, Phase 1).
 *
 * The finalized data model (DBDD §9.2 `customers`, §9.3 `staff`) does not
 * define credential-storage columns because authentication mechanics were
 * out of scope for the data model itself. Laravel's Authenticatable
 * contract requires, at minimum, a hashed password column; `remember_token`
 * and `email_verified_at` follow Laravel/Fortify convention for "remember
 * me" and (future) email verification.
 *
 * This is recorded here, in its own migration, specifically so it is
 * visible as an intentional addition on top of the finalized schema rather
 * than a silent divergence from the DBDD. Raise with the team for formal
 * sign-off the same way any other DBDD amendment would be ratified.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded per-column: an earlier pass of this migration (under a
        // filename since renamed) already added `password`/`remember_token`
        // to both tables in some environments. Only `email_verified_at` is
        // reliably still missing everywhere, but every column here stays
        // idempotent so this migration is safe regardless of which ran.
        Schema::table('staff', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff', 'password')) {
                $table->string('password');
            }
            if (! Schema::hasColumn('staff', 'remember_token')) {
                $table->rememberToken();
            }
            if (! Schema::hasColumn('staff', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });

        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'password')) {
                $table->string('password');
            }
            if (! Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken();
            }
            if (! Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });
    }
};
