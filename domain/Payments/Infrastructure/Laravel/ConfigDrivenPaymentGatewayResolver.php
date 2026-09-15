<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Contracts\PaymentGateway;
use Domain\Payments\Application\Contracts\PaymentGatewayResolver;
use Domain\Payments\Domain\Exceptions\UnsupportedPaymentMethod;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Illuminate\Contracts\Container\Container;

/**
 * Reads `config('payments.gateways')` (method => class-string) — adding a
 * real online provider later is one new config entry plus one new class,
 * never a change to this resolver, the PaymentGateway contract, or any
 * caller. `in_app` is absent from the map today, so it throws
 * UnsupportedPaymentMethod rather than pretending to support it.
 */
final class ConfigDrivenPaymentGatewayResolver implements PaymentGatewayResolver
{
    /** @param  array<string, class-string<PaymentGateway>>  $gateways */
    public function __construct(
        private readonly Container $container,
        private readonly array $gateways,
    ) {}

    public function resolve(PaymentMethod $method): PaymentGateway
    {
        $class = $this->gateways[$method->value] ?? null;

        if ($class === null) {
            throw UnsupportedPaymentMethod::forMethod($method->value);
        }

        return $this->container->make($class);
    }
}
