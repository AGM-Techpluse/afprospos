<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DBDD §15.1. A warranty is a configured policy, not a yes/no flag on a sale (BLD §5.3) -- coverage window, scope, exclusions, and permitted remedies are all admin-configurable per policy. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_policies', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('name', 150);
            $table->unsignedInteger('coverage_duration_days');
            $table->enum('coverage_start_point', ['sale_date', 'collection_date']);
            $table->json('covered_scope');
            $table->json('exclusions');
            $table->json('available_remedies');
            $table->enum('coverage_extent', ['full', 'percentage', 'fixed_amount', 'labour_only', 'parts_only']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_policies');
    }
};
