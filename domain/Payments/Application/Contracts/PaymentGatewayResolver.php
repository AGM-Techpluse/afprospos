<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use Domain\Payments\Domain\Exceptions\UnsupportedPaymentMethod;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;

/** Payments-internal — only Payments' own Handlers depend on this, never another module. */
interface PaymentGatewayResolver
{
    /** @throws UnsupportedPaymentMethod when no adapter is registered for the method (e.g. `in_app` today). */
    public function resolve(PaymentMethod $method): PaymentGateway;
}
