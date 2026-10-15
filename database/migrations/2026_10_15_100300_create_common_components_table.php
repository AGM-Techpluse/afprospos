<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The quick-check component list offered at diagnosis-case intake (e.g.
 * "Screen", "Battery") — device-type-agnostic, unlike device_problem_tags,
 * so no device_type_id. Previously a hardcoded frontend array
 * (CommonComponentsCard.tsx); now an admin-editable catalog, same shape
 * as device_problem_tags (label, sort_order, optional flat commission).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_components', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('label', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('commission_amount_minor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_components');
    }
};
