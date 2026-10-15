<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\CommonComponentId;

/** A quick-check component offered at diagnosis-case intake (e.g. "Screen", "Battery") — device-type-agnostic, unlike DeviceProblemTag, so a technician can flag the device's general physical/functional state before full diagnosis. */
final class CommonComponent
{
    private function __construct(
        private readonly ?CommonComponentId $id,
        private string $label,
        private int $sortOrder,
        private ?int $commissionAmountMinor = null,
    ) {}

    public static function create(string $label, int $sortOrder, ?int $commissionAmountMinor = null): self
    {
        return new self(null, $label, $sortOrder, $commissionAmountMinor);
    }

    public static function reconstitute(CommonComponentId $id, string $label, int $sortOrder, ?int $commissionAmountMinor = null): self
    {
        return new self($id, $label, $sortOrder, $commissionAmountMinor);
    }

    public function rename(string $label): void
    {
        $this->label = $label;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /** The flat technician commission earned when a fault tied to this component is ticked as actually fixed. Null means not commissionable. */
    public function setCommissionAmount(?int $amountMinor): void
    {
        $this->commissionAmountMinor = $amountMinor;
    }

    public function id(): ?CommonComponentId
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function commissionAmountMinor(): ?int
    {
        return $this->commissionAmountMinor;
    }
}
