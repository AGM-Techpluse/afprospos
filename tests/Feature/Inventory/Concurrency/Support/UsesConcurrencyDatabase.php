<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory\Concurrency\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * SQLite :memory: (the default test DB, phpunit.xml) is not shareable
 * across OS processes — each gets its own empty in-memory database.
 * A genuinely-parallel test therefore needs a real, persistent MySQL
 * database both this process and a spawned child process connect to.
 * Uses a dedicated `afprospos_concurrency_test` schema — never the
 * real dev `afprospos` database — created here, not by hand.
 */
trait UsesConcurrencyDatabase
{
    protected function setUpConcurrencyDatabase(): void
    {
        $mysqlConfig = config('database.connections.mysql');

        $pdo = new PDO(
            "mysql:host={$mysqlConfig['host']};port={$mysqlConfig['port']}",
            $mysqlConfig['username'],
            $mysqlConfig['password'],
        );
        $pdo->exec('CREATE DATABASE IF NOT EXISTS afprospos_concurrency_test');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'afprospos_concurrency_test',
        ]);
        DB::purge('mysql');

        Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]);
    }

    protected function tearDownConcurrencyDatabase(): void
    {
        foreach (['inventory_transfers', 'inventory_items', 'inventory_stock_levels', 'inventory_skus', 'inventory_products', 'staff', 'shops'] as $table) {
            DB::connection('mysql')->table($table)->delete();
        }
    }
}
