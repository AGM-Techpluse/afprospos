<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentSaleLookup implements SaleLookup
{
    public function find(int $saleId): ?array
    {
        $sale = SaleRecord::query()->with('checkout.items')->find($saleId);

        if ($sale === null) {
            return null;
        }

        return [
            'id' => $sale->id,
            'shop_id' => $sale->shop_id,
            'customer_id' => $sale->customer_id,
            'total_minor' => $sale->total_minor,
            'invoice_number' => $sale->invoice_number,
            'payment_transaction_id' => $sale->payment_transaction_id,
            'created_at' => $sale->created_at->toIso8601String(),
            'items' => $sale->checkout->items->map(static fn ($item): array => [
                'sku_id' => $item->sku_id,
                'inventory_item_id' => $item->inventory_item_id,
            ])->all(),
        ];
    }
}
