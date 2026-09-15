<?php

declare(strict_types=1);
use Domain\Payments\Infrastructure\Gateways\Manual\ManualBankTransferGateway;
use Domain\Payments\Infrastructure\Gateways\Manual\ManualCashGateway;
use Domain\Payments\Infrastructure\Gateways\Manual\ManualPosTerminalGateway;

/**
 * The seam a future online gateway plugs into — no credentials here yet
 * (Phase 5 scope: no online provider is integrated). Adding one later is
 * one new entry (e.g. 'in_app' => PaystackGateway::class) plus one new
 * class, never a refactor of the resolver, the PaymentGateway contract,
 * or any caller.
 */
return [

    'gateways' => [
        'cash' => ManualCashGateway::class,
        'pos_terminal' => ManualPosTerminalGateway::class,
        'bank_transfer' => ManualBankTransferGateway::class,
        // 'in_app' intentionally absent — no online gateway integrated yet.
    ],

];
