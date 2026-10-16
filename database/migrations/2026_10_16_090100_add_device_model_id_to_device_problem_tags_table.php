<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Null = a type-level/fallback tag (today's behavior); set = a model-specific override. device_type_id stays required on every row (denormalized) so type-level fallback queries never need to join through brand. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_problem_tags', function (Blueprint $table): void {
            $table->foreignId('device_model_id')->nullable()->after('device_type_id')
                ->constrained('device_models')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('device_problem_tags', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('device_model_id');
        });
    }
};
