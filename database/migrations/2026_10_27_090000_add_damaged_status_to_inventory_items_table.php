<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lets a specific serialized unit be marked damaged (INV-BR-12-style
 * manual correction, but per-unit rather than a bulk count) — a
 * `status` value, not `condition`, since `status` is what already gates
 * reservability (InventoryItem::reserve()/isAvailable() check `status`
 * only): a damaged unit stopping availability is then automatic,
 * everywhere, with no change to reservation logic at all.
 */
return new class extends Migration
{
    private const STATUSES = ['available', 'reserved', 'sold', 'transferring', 'damaged'];

    public function up(): void
    {
        $this->alterStatusEnum(self::STATUSES);
    }

    public function down(): void
    {
        DB::table('inventory_items')->where('status', 'damaged')->update(['status' => 'available']);
        $this->alterStatusEnum(['available', 'reserved', 'sold', 'transferring']);
    }

    /** @param  string[]  $statuses */
    private function alterStatusEnum(array $statuses): void
    {
        $quoted = implode("','", $statuses);
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE inventory_items MODIFY status ENUM('{$quoted}') NOT NULL");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys=off');
        DB::statement("CREATE TABLE inventory_items_rebuild (
            id integer primary key autoincrement not null,
            sku_id integer not null,
            imei varchar not null,
            serial_number varchar,
            current_shop_id integer not null,
            condition varchar not null check (condition in ('new','used_grade_a','used_grade_b','used_grade_c','refurbished')),
            status varchar not null check (status in ('{$quoted}')),
            reserved_by_type varchar check (reserved_by_type in ('repair','checkout')),
            reserved_by_id integer,
            version integer not null default 0,
            created_at datetime,
            updated_at datetime,
            foreign key(sku_id) references inventory_skus(id) on delete restrict on update restrict,
            foreign key(current_shop_id) references shops(id) on delete restrict on update restrict
        )");
        DB::statement('INSERT INTO inventory_items_rebuild SELECT id, sku_id, imei, serial_number, current_shop_id, condition, status, reserved_by_type, reserved_by_id, version, created_at, updated_at FROM inventory_items');
        DB::statement('DROP TABLE inventory_items');
        DB::statement('ALTER TABLE inventory_items_rebuild RENAME TO inventory_items');
        DB::statement('CREATE UNIQUE INDEX inventory_items_imei_unique ON inventory_items (imei)');
        DB::statement('CREATE UNIQUE INDEX inventory_items_serial_number_unique ON inventory_items (serial_number)');
        DB::statement('CREATE INDEX inventory_items_shop_status_idx ON inventory_items (current_shop_id, status)');
        DB::statement('PRAGMA foreign_keys=on');
    }
};
