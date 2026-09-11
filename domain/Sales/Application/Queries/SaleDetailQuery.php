<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Queries;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;

/** Backs Admin Sales/Show and Sales/Receipt. */
final class SaleDetailQuery
{
    public function __construct(private readonly InventoryCatalogQuery $catalog) {}

    /** @return array<string, mixed>|null */
    public function find(int $saleId): ?array
    {
        $sale = SaleRecord::query()->with(['checkout.items', 'checkout.adjustments'])->find($saleId);

        return $sale !== null ? $this->toArray($sale) : null;
    }

    /** @return array<string, mixed>|null */
    public function findByCheckoutId(int $checkoutId): ?array
    {
        $sale = SaleRecord::query()->with(['checkout.items', 'checkout.adjustments'])->where('sales_checkout_id', $checkoutId)->first();

        return $sale !== null ? $this->toArray($sale) : null;
    }

    /** @return array<string, mixed> */
    private function toArray(SaleRecord $sale): array
    {
        return [
            'id' => $sale->id,
            'sales_checkout_id' => $sale->sales_checkout_id,
            'shop_id' => $sale->shop_id,
            'customer_id' => $sale->customer_id,
            'cashier_staff_id' => $sale->cashier_staff_id,
            'total_minor' => $sale->total_minor,
            'payment_method' => $sale->payment_method,
            'payment_reference' => $sale->payment_reference,
            'invoice_number' => $sale->invoice_number,
            'created_at' => $sale->created_at->toIso8601String(),
            'items' => $sale->checkout->items->map(function ($item) use ($sale): array {
                $sku = $this->catalog->find($item->sku_id, $sale->shop_id);

                return [
                    'sku_id' => $item->sku_id,
                    'sku_code' => $sku['sku_code'] ?? null,
                    'product_name' => $sku['product_name'] ?? null,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $item->unit_price_minor,
                ];
            })->all(),
            'adjustments' => $sale->checkout->adjustments->map(static fn ($adjustment): array => [
                'type' => $adjustment->type,
                'amount_minor' => $adjustment->amount_minor,
            ])->all(),
            'subtotal_minor' => $sale->checkout->subtotal_minor,
            'discount_minor' => $sale->checkout->discount_minor,
        ];
    }
}
