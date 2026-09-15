<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Gateways\Manual;

use Domain\Payments\Application\Contracts\PaymentGateway;
use Domain\Payments\Application\DTOs\InitiatePaymentGatewayRequest;
use Domain\Payments\Application\DTOs\PaymentGatewayInitiationResult;
use Domain\Payments\Application\DTOs\PaymentGatewayRefundResult;
use Domain\Payments\Application\DTOs\RefundPaymentGatewayRequest;

/** Cash is witnessed by staff at capture time — nothing external to wait for, so it confirms immediately. `refund()` is bookkeeping only; handing cash back has no API to call. */
final class ManualCashGateway implements PaymentGateway
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
