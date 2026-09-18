<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Contracts\CheckoutDiscountService;
use Domain\Sales\Application\Handlers\ApplyDiscountHandler;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;

/** The Contract's default implementation — delegates to the Handler, mirrors Payments' HandlerBacked* services (CPNC §4.4). Absorbs `CheckoutNotOpen` here so it never crosses the module boundary into a caller like Warranty. */
final class HandlerBackedCheckoutDiscountService implements CheckoutDiscountService
{
    public function __construct(private readonly ApplyDiscountHandler $handler) {}

    public function apply(ApplyDiscountCommand $command): bool
    {
        try {
            $this->handler->handle($command);

            return true;
        } catch (CheckoutNotOpen) {
            return false;
        }
    }
}
