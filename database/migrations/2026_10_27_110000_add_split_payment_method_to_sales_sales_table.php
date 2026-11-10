<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lets a Sale's `payment_method` record 'split' — set when more than one
 * payments_transactions row actually contributed to reaching the total
 * (paid across two methods at once, or collected as several partial
 * payments over time) — see CollectCheckoutPaymentHandler. The Sale's own
 * `payment_transaction_id` column stays a single reference (the last
 * contributing transaction, for quick display); the full breakdown is
 * always derivable by querying payments_transactions for this sale's
 * sales_checkout_id, which was already possible before this migration —
 * no schema change needed for that part.
 */
return new class extends Migration
{
    private const METHODS = ['cash', 'pos_terminal', 'bank_transfer', 'in_app', 'split'];

    public function up(): void
    {
        $this->alterPaymentMethodEnum(self::METHODS);
    }

    public function down(): void
    {
        DB::table('sales_sales')->where('payment_method', 'split')->update(['payment_method' => 'cash']);
        $this->alterPaymentMethodEnum(['cash', 'pos_terminal', 'bank_transfer', 'in_app']);
    }

    /** @param  string[]  $methods */
    private function alterPaymentMethodEnum(array $methods): void
    {
        $quoted = implode("','", $methods);
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE sales_sales MODIFY payment_method ENUM('{$quoted}') NOT NULL");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys=off');
        DB::statement("CREATE TABLE sales_sales_rebuild (
            id integer primary key autoincrement not null,
            sales_checkout_id integer not null,
            shop_id integer not null,
            customer_id integer,
            walk_in_name varchar,
            walk_in_phone varchar,
            walk_in_email varchar,
            cashier_staff_id integer not null,
            total_minor integer not null,
            payment_transaction_id integer,
            payment_method varchar not null check (payment_method in ('{$quoted}')),
            payment_reference varchar,
            invoice_number varchar not null,
            created_at datetime not null default CURRENT_TIMESTAMP,
            foreign key(sales_checkout_id) references sales_checkouts(id) on delete restrict on update restrict,
            foreign key(shop_id) references shops(id) on delete restrict on update restrict,
            foreign key(customer_id) references customers(id) on delete set null on update restrict,
            foreign key(cashier_staff_id) references staff(id) on delete restrict on update restrict
        )");
        DB::statement('INSERT INTO sales_sales_rebuild SELECT id, sales_checkout_id, shop_id, customer_id, walk_in_name, walk_in_phone, walk_in_email, cashier_staff_id, total_minor, payment_transaction_id, payment_method, payment_reference, invoice_number, created_at FROM sales_sales');
        DB::statement('DROP TABLE sales_sales');
        DB::statement('ALTER TABLE sales_sales_rebuild RENAME TO sales_sales');
        DB::statement('CREATE UNIQUE INDEX sales_sales_invoice_number_unique ON sales_sales (invoice_number)');
        DB::statement('CREATE INDEX sales_sales_shop_created_idx ON sales_sales (shop_id, created_at)');
        DB::statement('PRAGMA foreign_keys=on');
    }
};
