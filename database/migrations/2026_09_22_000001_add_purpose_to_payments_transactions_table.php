<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `payable_type` alone can't tell a repair's intake down payment from its
 * completion settlement — both are `payable_type = 'repair_job'` — so
 * Admin's Payments list/detail pages had no way to show which one a row
 * was. `purpose` is the finer-grained, always-explicit classification the
 * initiating module (Sales/Repair) states at `InitiatePaymentCommand`
 * time; existing rows backfill deterministically since every row created
 * before this migration is either a full sale payment or a repair down
 * payment (repairs never created a completion-payment row until now).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Defaults to 'sale_payment' purely so the ADD COLUMN backfills
        // every existing row without a ->change() enum rewrite (avoids a
        // doctrine/dbal-dependent ALTER); application code always passes
        // an explicit purpose going forward regardless of this default.
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('purpose', ['sale_payment', 'repair_down_payment', 'repair_final_payment'])
                ->default('sale_payment')
                ->after('payable_id');
        });

        DB::table('payments_transactions')->where('payable_type', 'repair_job')->update(['purpose' => 'repair_down_payment']);
    }

    public function down(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->dropColumn('purpose');
        });
    }
};
