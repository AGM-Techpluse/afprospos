<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Queries;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;

/** Redisplays the live cart after every add/remove/discount action (Admin Checkout.tsx). */
final class CheckoutDetailQuery
{
    public function __construct(private readonly InventoryCatalogQuery $catalog) {}

    /** @return array<string, mixed>|null */
    public function find(int $checkoutId): ?array
    {
        $checkout = SalesCheckoutRecord::query()->with(['items', 'adjustments'])->find($checkoutId);

        return $checkout !== null ? $this->toArray($checkout) : null;
    }

    /** Lets a cashier resume the open checkout they were already building for this shop instead of losing track of it on navigation (SALE/INV-BR-06 still expires it on its own if truly abandoned). */
    public function findOpenForCashier(int $cashierStaffId, int $shopId): ?array
    {
        $checkout = SalesCheckoutRecord::query()
            ->with(['items', 'adjustments'])
            ->where('cashier_staff_id', $cashierStaffId)
            ->where('shop_id', $shopId)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        return $checkout !== null ? $this->toArray($checkout) : null;
    }

    /** @return array<string, mixed> */
    private function toArray(SalesCheckoutRecord $checkout): array
    {
        return [
            'id' => $checkout->id,
            'shop_id' => $checkout->shop_id,
            'customer_id' => $checkout->customer_id,
            'cashier_staff_id' => $checkout->cashier_staff_id,
            'status' => $checkout->status,
            'reservation_expires_at' => $checkout->reservation_expires_at->toIso8601String(),
            'subtotal_minor' => $checkout->subtotal_minor,
            'discount_minor' => $checkout->discount_minor,
            'total_minor' => $checkout->total_minor,
            'items' => $checkout->items->map(function ($item) use ($checkout): array {
                $sku = $this->catalog->find($item->sku_id, $checkout->shop_id);

                return [
                    'id' => $item->id,
                    'sku_id' => $item->sku_id,
                    'sku_code' => $sku['sku_code'] ?? null,
                    'product_name' => $sku['product_name'] ?? null,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $item->unit_price_minor,
                ];
            })->all(),
            'adjustments' => $checkout->adjustments->map(static fn ($adjustment): array => [
                'id' => $adjustment->id,
                'type' => $adjustment->type,
                'source_id' => $adjustment->source_id,
                'amount_minor' => $adjustment->amount_minor,
                'applied_order' => $adjustment->applied_order,
            ])->all(),
        ];
    }
}
