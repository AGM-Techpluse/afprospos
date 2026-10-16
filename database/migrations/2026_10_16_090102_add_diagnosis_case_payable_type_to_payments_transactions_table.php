<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets a repair_diagnosis_fee payment be collected against a diagnosis case before a repair job exists — mirrors how sales_checkout is paid before the Sale aggregate exists. */
return new class extends Migration
{
    private const PAYABLE_TYPES = ['sales_checkout', 'repair_job', 'warranty_claim', 'return_request', 'diagnosis_case'];

    public function up(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('payable_type', self::PAYABLE_TYPES)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('payable_type', array_values(array_diff(self::PAYABLE_TYPES, ['diagnosis_case'])))->change();
        });
    }
};
