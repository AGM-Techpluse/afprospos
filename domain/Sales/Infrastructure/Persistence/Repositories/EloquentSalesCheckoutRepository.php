<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Repositories;

use Domain\Sales\Domain\Entities\SalesCheckout;
use Domain\Sales\Domain\Entities\SalesCheckoutAdjustment;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutAdjustmentId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Carbon;

final class EloquentSalesCheckoutRepository implements SalesCheckoutRepository
{
    public function get(CheckoutId $id): SalesCheckout
    {
        $record = SalesCheckoutRecord::query()->with(['items', 'adjustments'])->findOrFail($id->value);

        return $this->toDomain($record);
    }

    /** Locks the checkout row (ADD §16's "lock checkout" step) — the serialization point for expiry racing payment confirmation. */
    public function lockForUpdate(CheckoutId $id): SalesCheckout
    {
        $record = SalesCheckoutRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();
        $record->load(['items', 'adjustments']);

        return $this->toDomain($record);
    }

    public function save(SalesCheckout $checkout): CheckoutId
    {
        if ($checkout->id() === null) {
            $record = SalesCheckoutRecord::query()->create([
                'shop_id' => $checkout->shopId()->value,
                'customer_id' => $checkout->customerId()?->value,
                'cashier_staff_id' => $checkout->cashierStaffId()->value,
                'status' => $checkout->status(),
                'reservation_expires_at' => $checkout->reservationExpiresAt(),
                'subtotal_minor' => $checkout->subtotalMinor(),
                'discount_minor' => $checkout->discountMinor(),
                'total_minor' => $checkout->totalMinor(),
            ]);
        } else {
            $record = SalesCheckoutRecord::query()->findOrFail($checkout->id()->value);
            $record->update([
                'status' => $checkout->status(),
                'subtotal_minor' => $checkout->subtotalMinor(),
                'discount_minor' => $checkout->discountMinor(),
                'total_minor' => $checkout->totalMinor(),
            ]);
        }

        $this->syncItems($record, $checkout);
        $this->syncAdjustments($record, $checkout);

        return new CheckoutId($record->id);
    }

    /** @return int[] */
    public function findExpirableIds(int $limit): array
    {
        return SalesCheckoutRecord::query()
            ->where('status', 'open')
            ->where('reservation_expires_at', '<=', Carbon::now())
            ->orderBy('reservation_expires_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    private function syncItems(SalesCheckoutRecord $record, SalesCheckout $checkout): void
    {
        $keepIds = array_filter(array_map(static fn (SalesCheckoutItem $item): ?int => $item->id()?->value, $checkout->items()));

        $record->items()->whereNotIn('id', $keepIds === [] ? [0] : $keepIds)->delete();

        foreach ($checkout->items() as $item) {
            if ($item->id() === null) {
                $record->items()->create([
                    'inventory_item_id' => $item->inventoryItemId(),
                    'sku_id' => $item->skuId(),
                    'quantity' => $item->quantity(),
                    'unit_price_minor' => $item->unitPrice()->minor,
                ]);
            }
        }
    }

    private function syncAdjustments(SalesCheckoutRecord $record, SalesCheckout $checkout): void
    {
        $record->adjustments()->delete();

        foreach ($checkout->adjustments() as $adjustment) {
            $record->adjustments()->create([
                'type' => $adjustment->type(),
                'source_id' => $adjustment->sourceId(),
                'amount_minor' => $adjustment->amount()->minor,
                'applied_order' => $adjustment->appliedOrder(),
            ]);
        }
    }

    private function toDomain(SalesCheckoutRecord $record): SalesCheckout
    {
        $items = $record->items->map(static fn ($item): SalesCheckoutItem => SalesCheckoutItem::reconstitute(
            new SalesCheckoutItemId($item->id),
            $item->sku_id,
            $item->inventory_item_id,
            $item->quantity,
            new Money($item->unit_price_minor),
        ))->all();

        $adjustments = $record->adjustments->map(static fn ($adjustment): SalesCheckoutAdjustment => SalesCheckoutAdjustment::reconstitute(
            new SalesCheckoutAdjustmentId($adjustment->id),
            $adjustment->type,
            $adjustment->source_id,
            new Money($adjustment->amount_minor),
            $adjustment->applied_order,
        ))->all();

        return SalesCheckout::reconstitute(
            new CheckoutId($record->id),
            new ShopId($record->shop_id),
            $record->customer_id !== null ? new CustomerId($record->customer_id) : null,
            new StaffId($record->cashier_staff_id),
            $record->status,
            $record->reservation_expires_at->toDateTimeImmutable(),
            $items,
            $adjustments,
            $record->subtotal_minor,
            $record->discount_minor,
            $record->total_minor,
        );
    }
}
