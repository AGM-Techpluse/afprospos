<?php

declare(strict_types=1);

/**
 * A genuinely separate OS process's single attempt to either expire a
 * checkout or complete it as a paid sale — mirrors Inventory's
 * attempt_reservation.php exactly (same MySQL bootstrap, same
 * DB::listen hold-ms hook on the checkout row's SELECT ... FOR UPDATE).
 *
 * Usage: php attempt_expire_or_complete.php --checkout=1 --action=expire|complete
 *   --staff=1 [--shop-code=LGS] [--hold-ms=300]
 */

require __DIR__.'/../../../../../vendor/autoload.php';

use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Commands\ExpireCheckoutCommand;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Handlers\ExpireCheckoutHandler;
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

$options = getopt('', ['checkout:', 'action:', 'staff:', 'shop-code::', 'hold-ms::']);
$holdMs = isset($options['hold-ms']) ? (int) $options['hold-ms'] : 0;

if ($holdMs > 0) {
    DB::listen(function ($query) use ($holdMs): void {
        if (stripos($query->sql, 'for update') !== false) {
            usleep($holdMs * 1000);
        }
    });
}

$checkoutId = (int) $options['checkout'];

try {
    if ($options['action'] === 'expire') {
        $app->make(ExpireCheckoutHandler::class)->handle(new ExpireCheckoutCommand($checkoutId));
    } else {
        $app->make(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: (int) $options['staff'],
            shopCode: $options['shop-code'] ?? 'LGS',
        ));
    }

    fwrite(STDOUT, json_encode(['success' => true]));
} catch (Throwable $e) {
    fwrite(STDOUT, json_encode(['success' => false, 'error' => $e::class, 'message' => $e->getMessage()]));
}
