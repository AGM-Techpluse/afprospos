<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const DEFAULT_CONDITIONS = [
        'new',
        'used_grade_a',
        'used_grade_b',
        'used_grade_c',
        'refurbished',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // `condition` is a reserved SQL keyword (SQL:2003 condition
            // handlers) — must be backtick-quoted in raw SQL, unlike the
            // Schema builder branch below, which quotes it automatically.
            DB::statement('ALTER TABLE inventory_items MODIFY `condition` VARCHAR(120) NOT NULL');
        } else {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $table->string('condition', 120)->change();
            });
        }

        $now = now();

        DB::table('inventory_master_entries')->insertOrIgnore(array_map(
            static fn (string $condition): array => [
                'kind' => 'condition',
                'name' => $condition,
                'contact_phone' => null,
                'contact_email' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::DEFAULT_CONDITIONS,
        ));
    }

    public function down(): void
    {
        DB::table('inventory_items')
            ->whereNotIn('condition', self::DEFAULT_CONDITIONS)
            ->update(['condition' => 'new']);

        DB::table('inventory_master_entries')
            ->where('kind', 'condition')
            ->whereNotIn('name', self::DEFAULT_CONDITIONS)
            ->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_items MODIFY `condition` ENUM('new', 'used_grade_a', 'used_grade_b', 'used_grade_c', 'refurbished') NOT NULL");
        } else {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $table->enum('condition', self::DEFAULT_CONDITIONS)->change();
            });
        }

        DB::table('inventory_master_entries')
            ->where('kind', 'condition')
            ->delete();
    }
};
