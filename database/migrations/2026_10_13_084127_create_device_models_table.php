<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_models', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('device_brand_id')->constrained('device_brands')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['device_brand_id', 'name'], 'device_models_brand_name_unique');
            $table->index(['device_brand_id', 'sort_order'], 'device_models_brand_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_models');
    }
};
