<?php

declare(strict_types=1);

/**
 * A genuinely separate OS process's single reservation attempt — the
 * default SQLite :memory: test DB (phpunit.xml) is not shareable across
 * processes, so this boots Laravel manually (the way `artisan` does)
 * against the real, dedicated `afprospos_concurrency_test` MySQL
 * database both this script and the parent PHPUnit process connect to.
 *
 * Usage: php attempt_reservation.php --sku=1 --shop=1 --quantity=1
 *   --source-id=1 [--source-type=checkout] [--hold-ms=300]
 *
 * --hold-ms deliberately sleeps right after this process's SELECT ...
 * FOR UPDATE query returns (via a DB::listen hook, never touching
 * production code) so a second, later-started process genuinely has
 * to wait at the database lock rather than the race finishing before
 * it ever contends — this is what makes the test prove real blocking,
 * not just assert an outcome two sequential calls would also produce.
 */

require __DIR__.'/../../../../../vendor/autoload.php';

use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Handlers\ReserveInventoryHandler;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

/** @var Application $app */
$app = require __DIR__.'/../../../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'database.default' => 'mysql',
    'database.connections.mysql.database' => 'afprospos_concurrency_test',
]);
DB::purge('mysql');

$options = getopt('', ['sku:', 'shop:', 'quantity:', 'source-id:', 'source-type::', 'hold-ms::']);
$holdMs = isset($options['hold-ms']) ? (int) $options['hold-ms'] : 0;

if ($holdMs > 0) {
    DB::listen(function ($query) use ($holdMs): void {
        if (stripos($query->sql, 'for update') !== false) {
            usleep($holdMs * 1000);
        }
    });
}

$command = new ReserveInventoryCommand(
    skuId: (int) $options['sku'],
    shopId: (int) $options['shop'],
    quantity: (int) $options['quantity'],
    sourceType: $options['source-type'] ?? 'checkout',
    sourceId: (int) $options['source-id'],
);

try {
    $result = $app->make(ReserveInventoryHandler::class)->handle($command);
    fwrite(STDOUT, json_encode(['success' => true, 'item_ids' => $result->inventoryItemIds]));
} catch (Throwable $e) {
    fwrite(STDOUT, json_encode(['success' => false, 'error' => $e::class, 'message' => $e->getMessage()]));
}
