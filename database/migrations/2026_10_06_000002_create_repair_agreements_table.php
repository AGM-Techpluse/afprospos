<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Special agreements recorded when a manager authorizes a quote. Kept as history, one row per agreement. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_agreements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('repair_job_id');
            $table->text('note');
            $table->unsignedBigInteger('recorded_by_staff_id');
            $table->timestamps();

            $table->index('repair_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_agreements');
    }
};
