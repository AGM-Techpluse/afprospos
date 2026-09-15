<?php

declare(strict_types=1);

/**
 * A genuinely separate OS process's single attempt to confirm a payment
 * transaction — mirrors Sales' attempt_expire_or_complete.php exactly
 * (same MySQL bootstrap, same DB::listen hold-ms hook on the transaction
 * row's SELECT ... FOR UPDATE).
 *
 * Usage: php attempt_confirm_payment.php --transaction=1 --staff=1 [--hold-ms=300]
 */

require __DIR__.'/../../../../../vendor/autoload.php';

use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Handlers\ConfirmPaymentHandler;
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

$options = getopt('', ['transaction:', 'staff:', 'hold-ms::']);
$holdMs = isset($options['hold-ms']) ? (int) $options['hold-ms'] : 0;

if ($holdMs > 0) {
    DB::listen(function ($query) use ($holdMs): void {
        if (stripos($query->sql, 'for update') !== false) {
            usleep($holdMs * 1000);
        }
    });
}

try {
    $app->make(ConfirmPaymentHandler::class)->handle(new ConfirmPaymentCommand(
        transactionId: (int) $options['transaction'],
        confirmedByStaffId: (int) $options['staff'],
    ));

    fwrite(STDOUT, json_encode(['success' => true]));
} catch (Throwable $e) {
    fwrite(STDOUT, json_encode(['success' => false, 'error' => $e::class, 'message' => $e->getMessage()]));
}
