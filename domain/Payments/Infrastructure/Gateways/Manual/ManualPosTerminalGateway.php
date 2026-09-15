<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Gateways\Manual;

use Domain\Payments\Application\Contracts\PaymentGateway;
use Domain\Payments\Application\DTOs\InitiatePaymentGatewayRequest;
use Domain\Payments\Application\DTOs\PaymentGatewayInitiationResult;
use Domain\Payments\Application\DTOs\PaymentGatewayRefundResult;
use Domain\Payments\Application\DTOs\RefundPaymentGatewayRequest;

/**
 * POS-terminal capture is witnessed by staff at the terminal — confirms
 * immediately, same as cash. Kept as its own class (not merged with
 * ManualCashGateway) because this is the method most likely to grow a
 * real terminal-reconciliation API call later; splitting later would be
 * a bigger change than keeping them separate now.
 */
final class ManualPosTerminalGateway implements PaymentGateway
{
    public function initiate(InitiatePaymentGatewayRequest $request): PaymentGatewayInitiationResult
    {
        return new PaymentGatewayInitiationResult('confirmed', $request->providerReference);
    }

    public function refund(RefundPaymentGatewayRequest $request): PaymentGatewayRefundResult
    {
        return new PaymentGatewayRefundResult($request->providerReference);
    }
}
