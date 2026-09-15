<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use Domain\Payments\Application\DTOs\InitiatePaymentGatewayRequest;
use Domain\Payments\Application\DTOs\PaymentGatewayInitiationResult;
use Domain\Payments\Application\DTOs\PaymentGatewayRefundResult;
use Domain\Payments\Application\DTOs\RefundPaymentGatewayRequest;

/**
 * The seam a future online provider plugs into (CPNC's own worked
 * example for this exact module). Deliberately has no `verify()`/
 * webhook-shaped method — nothing in this phase calls one, since no
 * online gateway is integrated yet; a future `PaystackGateway` adds
 * verification as its own implementation detail without changing this
 * contract's shape.
 */
interface PaymentGateway
{
    public function initiate(InitiatePaymentGatewayRequest $request): PaymentGatewayInitiationResult;

    public function refund(RefundPaymentGatewayRequest $request): PaymentGatewayRefundResult;
}
