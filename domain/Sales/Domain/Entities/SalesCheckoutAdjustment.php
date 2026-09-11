<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Entities;

use Domain\Sales\Domain\Services\CheckoutPricingService;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutAdjustmentId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * A single priced adjustment against a checkout (DBDD §13.3, MKT-BR-07/08).
 * `sourceId` is an opaque cross-module reference — none of promotion/
 * referral/trade-in/store-credit's owning modules exist yet, so nothing
 * validates it, matching how Commission stores source_type/source_id.
 */
final class SalesCheckoutAdjustment
{
    private function __construct(
        private readonly ?SalesCheckoutAdjustmentId $id,
        private readonly string $type,
        private readonly int $sourceId,
        private readonly Money $amount,
        private readonly int $appliedOrder,
    ) {}

    public static function create(string $type, int $sourceId, Money $amount): self
    {
        return new self(null, $type, $sourceId, $amount, CheckoutPricingService::appliedOrderFor($type));
    }

    public static function reconstitute(
        SalesCheckoutAdjustmentId $id,
        string $type,
        int $sourceId,
        Money $amount,
        int $appliedOrder,
    ): self {
        return new self($id, $type, $sourceId, $amount, $appliedOrder);
    }

    public function id(): ?SalesCheckoutAdjustmentId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function sourceId(): int
    {
        return $this->sourceId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function appliedOrder(): int
    {
        return $this->appliedOrder;
    }
}
