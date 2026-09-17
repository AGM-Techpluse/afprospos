<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Commands\RefundPaymentCommand;
use Domain\Payments\Application\Contracts\PaymentRefundService;
use Domain\Payments\Application\Handlers\RefundPaymentHandler;

/** The Contract's default implementation — delegates straight to the Handler, mirrors HandlerBackedPaymentInitiationService (CPNC §4.4). */
final class HandlerBackedPaymentRefundService implements PaymentRefundService
{
    public function __construct(private readonly RefundPaymentHandler $handler) {}

    public function refund(RefundPaymentCommand $command): void
    {
        $this->handler->handle($command);
    }
}
