<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_ledger_entries', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('source_type', [
                'sale',
                'repair',
            ]);

            // Cross-module source ID — intentionally no FK (CPNC §4.5).
            $table->unsignedBigInteger('source_id');

            $table->bigInteger('commissionable_amount_minor');

            $table->decimal('rate_snapshot_percent', 5, 2);

            $table->string('role_snapshot');

            $table->enum('entry_type', [
                'earned',
                'reversal',
                'adjustment',
            ]);

            $table->unsignedBigInteger('reversal_of_entry_id')
                ->nullable();

            $table->enum('status', [
                'earned',
                'paid',
            ]);

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            /*
             * IMPORTANT:
             *
             * Do NOT call $table->timestamps().
             *
             * This table is append-only and deliberately has no
             * updated_at column (DBDD §17.1).
             */
            $table->timestamp('created_at')
                ->useCurrent();

            $table->foreign('reversal_of_entry_id')
                ->references('id')
                ->on('commission_ledger_entries')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(
                ['staff_id', 'created_at'],
                'commission_entries_staff_created_idx'
            );

            $table->index(
                ['shop_id', 'created_at'],
                'commission_entries_shop_created_idx'
            );

            $table->index(
                ['source_type', 'source_id'],
                'commission_entries_source_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ledger_entries');
    }
};
