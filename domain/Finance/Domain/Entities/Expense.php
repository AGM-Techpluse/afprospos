<?php

declare(strict_types=1);

namespace Domain\Finance\Domain\Entities;

use Domain\Finance\Domain\ValueObjects\ExpenseId;

/**
 * A single recorded business cost (salaries, rent, utilities, ...) — DBDD §22.2.
 * Mutable (unlike an append-only ledger entry) since the schema carries `updated_at`:
 * a mistyped amount/category is corrected in place, never deleted — financial
 * records are corrected, not erased.
 */
final class Expense
{
    private function __construct(
        private readonly ?ExpenseId $id,
        private readonly ?int $shopId,
        private string $category,
        private int $amountMinor,
        /** @var array<string, int>|null shop_id => amount_minor, when a shared expense is split across shops */
        private ?array $allocation,
        private ?string $note,
        private readonly int $recordedByStaffId,
    ) {}

    public static function record(
        ?int $shopId,
        string $category,
        int $amountMinor,
        ?array $allocation,
        ?string $note,
        int $recordedByStaffId,
    ): self {
        return new self(null, $shopId, $category, $amountMinor, $allocation, $note, $recordedByStaffId);
    }

    public static function reconstitute(
        ExpenseId $id,
        ?int $shopId,
        string $category,
        int $amountMinor,
        ?array $allocation,
        ?string $note,
        int $recordedByStaffId,
    ): self {
        return new self($id, $shopId, $category, $amountMinor, $allocation, $note, $recordedByStaffId);
    }

    /** A correction after the fact — e.g. a mistyped amount or wrong category. */
    public function update(string $category, int $amountMinor, ?array $allocation, ?string $note): void
    {
        $this->category = $category;
        $this->amountMinor = $amountMinor;
        $this->allocation = $allocation;
        $this->note = $note;
    }

    public function id(): ?ExpenseId
    {
        return $this->id;
    }

    public function shopId(): ?int
    {
        return $this->shopId;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    /** @return array<string, int>|null */
    public function allocation(): ?array
    {
        return $this->allocation;
    }

    public function note(): ?string
    {
        return $this->note;
    }

    public function recordedByStaffId(): int
    {
        return $this->recordedByStaffId;
    }
}
