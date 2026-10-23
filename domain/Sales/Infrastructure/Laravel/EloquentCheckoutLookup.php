<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Sales\Application\Contracts\CheckoutLookup;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentCheckoutLookup implements CheckoutLookup
{
    public function find(int $checkoutId): ?array
    {
        $checkout = SalesCheckoutRecord::query()->find($checkoutId);

        if ($checkout === null) {
            return null;
        }

        return [
            'id' => $checkout->id,
            'shop_id' => $checkout->shop_id,
            'customer_id' => $checkout->customer_id,
            'walk_in_name' => $checkout->walk_in_name,
        ];
    }
}
