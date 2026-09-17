<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Contracts\PaymentGatewayResolver;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Payments\Application\Contracts\PaymentRefundService;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Infrastructure\Persistence\Repositories\EloquentPaymentTransactionRepository;
use Illuminate\Support\ServiceProvider;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentTransactionRepository::class, EloquentPaymentTransactionRepository::class);
        $this->app->bind(PaymentInitiationService::class, HandlerBackedPaymentInitiationService::class);
        $this->app->bind(PaymentRefundService::class, HandlerBackedPaymentRefundService::class);

        $this->app->bind(PaymentGatewayResolver::class, function ($app): ConfigDrivenPaymentGatewayResolver {
            return new ConfigDrivenPaymentGatewayResolver($app, (array) config('payments.gateways', []));
        });
    }
}
