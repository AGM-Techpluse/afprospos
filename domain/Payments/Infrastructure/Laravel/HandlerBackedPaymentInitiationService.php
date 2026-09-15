<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Payments\Application\DTOs\PaymentInitiationResult;
use Domain\Payments\Application\Handlers\InitiatePaymentHandler;

/** The Contract's default implementation — delegates straight to the Handler, mirrors HandlerBackedInventoryReservationService (CPNC §4.4). */
final class HandlerBackedPaymentInitiationService implements PaymentInitiationService
{
    public function __construct(private readonly InitiatePaymentHandler $handler) {}

    public function initiate(InitiatePaymentCommand $command): PaymentInitiationResult
    {
        return $this->handler->handle($command);
    }
}
