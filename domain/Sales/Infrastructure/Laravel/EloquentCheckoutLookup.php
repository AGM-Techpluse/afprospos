<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Sales\Application\Contracts\CheckoutLookup;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentCheckoutLookup implements CheckoutLookup
{
    public function __construct(
        private readonly InventoryCatalogQuery $catalog,
    ) {}

    public function find(int $checkoutId): ?array
    {
        $checkout = SalesCheckoutRecord::query()->with('items')->find($checkoutId);

        if ($checkout === null) {
            return null;
        }

        return [
            'id' => $checkout->id,
            'shop_id' => $checkout->shop_id,
            'customer_id' => $checkout->customer_id,
            'walk_in_name' => $checkout->walk_in_name,
            'subtotal_minor' => $checkout->subtotal_minor,
            'discount_minor' => $checkout->discount_minor,
            'total_minor' => $checkout->total_minor,
            'items' => $checkout->items->map(function ($item) use ($checkout): array {
                $sku = $this->catalog->find($item->sku_id, $checkout->shop_id);

                return [
                    'product_name' => $sku['product_name'] ?? null,
                    'variant_label' => $sku['variant_label'] ?? null,
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $item->unit_price_minor,
                    'line_total_minor' => $item->unit_price_minor * $item->quantity,
                ];
            })->all(),
        ];
    }
}
