<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional contact details for a walk-in with no customer account — only meaningful when customer_id is null. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->string('walk_in_name', 150)->nullable()->after('customer_id');
            $table->string('walk_in_phone', 30)->nullable()->after('walk_in_name');
            $table->string('walk_in_email', 150)->nullable()->after('walk_in_phone');
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn(['walk_in_name', 'walk_in_phone', 'walk_in_email']);
        });
    }
};
