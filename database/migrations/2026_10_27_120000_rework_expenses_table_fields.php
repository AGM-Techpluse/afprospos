<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first pass at `expenses` (DBDD §22.2's plain column list) wasn't
 * what the stakeholder actually described when asked directly — this
 * reworks the table to the real fields: a name separate from the fixed
 * category, a department, the actual date the cost was incurred (not
 * just when it was entered), how it was paid, an uploaded proof
 * document, and whether it's a direct business disbursement or a
 * reimbursement to a specific staff member. `allocation` (cross-shop
 * split) wasn't part of what was actually asked for — dropped rather
 * than kept unused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('name', 150)->after('id');
            $table->string('department', 50)->after('shop_id');
            $table->date('expense_date')->after('amount_minor');
            $table->enum('payment_method', ['cash', 'pos_terminal', 'bank_transfer'])->after('expense_date');
            $table->string('proof_path')->nullable()->after('payment_method');
            $table->enum('fund_type', ['disbursement', 'reimbursement'])->default('disbursement')->after('note');

            $table->foreignId('reimbursed_to_staff_id')
                ->nullable()
                ->after('fund_type')
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->dropColumn('allocation');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->renameColumn('note', 'description');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->renameColumn('description', 'note');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reimbursed_to_staff_id');
            $table->dropColumn(['name', 'department', 'expense_date', 'payment_method', 'proof_path', 'fund_type']);
            $table->json('allocation')->nullable();
        });
    }
};
