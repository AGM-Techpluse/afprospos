<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Kernel table. Owned by the Shop bounded context.
 * Source: DBDD §9.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('name');
            $table->string('sku_prefix_code', 10);
            $table->string('address');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->json('offline_policy');
            $table->enum('status', ['active', 'inactive']);
            $table->timestamps();

            $table->unique('sku_prefix_code', 'shops_sku_prefix_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
