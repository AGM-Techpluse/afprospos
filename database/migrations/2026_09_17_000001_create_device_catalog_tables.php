<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Repair module, Phase 6).
 *
 * The finalized data model has no way for the shop to configure which
 * device types/brands/problem categories the intake flow offers — that
 * was hardcoded client-side (`Features/Repairs/DeviceTypePicker.tsx`).
 * This introduces a small admin-editable catalog: DeviceType -> Brand
 * (display/prefill only, brand itself is still free text on the job) and
 * DeviceType -> ProblemTag -> suggested Inventory SKUs (drives the Parts
 * page's auto-suggestion). Problem tags belong to the device *type*, not
 * the brand, since the set of typical problems (screen, battery, water
 * damage, ...) is the same across brands within a type — the brand step
 * only exists to prefill `device_make`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_types', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('label', 100);
            $table->string('icon', 50)->default('device-hdd');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('device_brands', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('device_type_id')->constrained('device_types')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['device_type_id', 'sort_order'], 'device_brands_type_sort_idx');
        });

        Schema::create('device_problem_tags', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('device_type_id')->constrained('device_types')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('label', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['device_type_id', 'sort_order'], 'device_problem_tags_type_sort_idx');
        });

        Schema::create('device_problem_suggested_skus', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('device_problem_tag_id')->constrained('device_problem_tags')->cascadeOnDelete()->cascadeOnUpdate();

            // Cross-module reference (CPNC §0.2): Inventory's sku_id, by
            // value only — never an FK constraint into another module's
            // table.
            $table->unsignedBigInteger('sku_id');
            $table->timestamps();

            $table->unique(['device_problem_tag_id', 'sku_id'], 'device_problem_suggested_skus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_problem_suggested_skus');
        Schema::dropIfExists('device_problem_tags');
        Schema::dropIfExists('device_brands');
        Schema::dropIfExists('device_types');
    }
};
