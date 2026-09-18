<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Contracts\PaymentConfirmationService;
use Domain\Payments\Application\Handlers\ConfirmPaymentHandler;

/** The Contract's default implementation — delegates straight to the Handler, mirrors HandlerBackedPaymentRefundService (CPNC §4.4). */
final class HandlerBackedPaymentConfirmationService implements PaymentConfirmationService
{
    public function __construct(private readonly ConfirmPaymentHandler $handler) {}

    public function confirm(ConfirmPaymentCommand $command): void
    {
        $this->handler->handle($command);
    }
}
