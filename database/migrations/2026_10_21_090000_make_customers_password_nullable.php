<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A walk-in customer (recorded during a sale/repair/diagnosis intake with
 * no self-service account) becomes a real `customers` row with no
 * password, rather than only free-text name/phone/email scattered across
 * four other tables with no shared identity — mirrors how `customer_id`
 * on those same tables is already nullable the other way round. A NULL
 * password can never authenticate (`EloquentCustomerRepository::verifyPassword()`
 * guards this explicitly, not just relying on `Hash::check()`'s behavior
 * against null), so "no account" stays exactly as true as it is today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });
    }
};
