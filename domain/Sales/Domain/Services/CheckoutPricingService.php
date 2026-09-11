<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Services;

use Domain\Sales\Domain\Entities\SalesCheckoutAdjustment;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Shared\Domain\ValueObjects\Money;
use InvalidArgumentException;

/**
 * Recomputes checkout totals from its items/adjustments (BLD §8.2,
 * MKT-BR-06/07/08). `applied_order` is assigned from a fixed type->order
 * map matching MKT-BR-07's canonical stacking order exactly — deterministic
 * regardless of the order adjustments were added in. Discount is clamped
 * at the subtotal (a checkout can never owe negative money).
 */
final class CheckoutPricingService
{
    /** MKT-BR-07: "promotion -> referral/voucher -> trade-in credit -> store credit". */
    private const TYPE_ORDER = [
        'promotion' => 1,
        'referral_voucher' => 2,
        'trade_in_credit' => 3,
        'store_credit' => 4,
    ];

    public static function appliedOrderFor(string $type): int
    {
        return self::TYPE_ORDER[$type]
            ?? throw new InvalidArgumentException("Unknown adjustment type: {$type}");
    }

    /**
     * @param  SalesCheckoutItem[]  $items
     * @param  SalesCheckoutAdjustment[]  $adjustments
     * @return array{subtotalMinor: int, discountMinor: int, totalMinor: int}
     */
    public static function recompute(array $items, array $adjustments): array
    {
        $subtotal = Money::zero();
        foreach ($items as $item) {
            $subtotal = $subtotal->add($item->lineTotal());
        }

        $discount = Money::zero();
        foreach ($adjustments as $adjustment) {
            $discount = $discount->add($adjustment->amount());
        }

        if ($discount->isGreaterThan($subtotal)) {
            $discount = $subtotal;
        }

        $total = $subtotal->subtract($discount);

        return [
            'subtotalMinor' => $subtotal->minor,
            'discountMinor' => $discount->minor,
            'totalMinor' => $total->minor,
        ];
    }
}
