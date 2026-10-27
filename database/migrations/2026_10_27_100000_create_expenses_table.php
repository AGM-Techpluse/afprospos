<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §22.2. `shop_id` NULL means a business-wide expense (not tied to
 * one shop); `allocation` is used when a shared expense is split across
 * several shops. `category` is a fixed catalog
 * (config('afprospos.expense_categories')), not a free-text/FK column —
 * same reasoning CPNC gives elsewhere for keeping a reporting axis clean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('shop_id')
                ->nullable()
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->string('category', 50);
            $table->bigInteger('amount_minor');
            $table->json('allocation')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('recorded_by_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->timestamps();

            $table->index(['shop_id', 'created_at'], 'expenses_shop_created_idx');
            $table->index('category', 'expenses_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
