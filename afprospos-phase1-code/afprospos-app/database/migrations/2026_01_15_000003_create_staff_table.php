<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Kernel table. Owned by the Identity bounded context.
 * Source: DBDD §9.3.
 *
 * NOTE: password/remember_token/email_verified_at are NOT part of the
 * finalized DBDD §9.3 column list. They are added in the follow-up
 * migration 2026_01_15_000004 as a documented, traceable amendment
 * required to satisfy Phase 1's authentication work package (Implementation
 * Plan §3, Phase 1, item 2). Keeping them in a separate migration makes the
 * DBDD-defined columns and the Identity-module addition independently
 * auditable in `git blame` / migration history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'deactivated'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
