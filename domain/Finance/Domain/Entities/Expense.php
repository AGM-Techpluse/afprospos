<?php

declare(strict_types=1);

namespace Domain\Finance\Domain\Entities;

use DateTimeImmutable;
use Domain\Finance\Domain\ValueObjects\ExpenseId;

/**
 * A single recorded business cost (salaries, rent, utilities, ...) — either
 * a direct business disbursement, or a reimbursement to a specific staff
 * member who paid for it themselves. Mutable (unlike an append-only ledger
 * entry): a mistyped amount/category is corrected in place, never deleted —
 * financial records are corrected, not erased.
 */
final class Expense
{
    private function __construct(
        private readonly ?ExpenseId $id,
        private string $name,
        private readonly ?int $shopId,
        private string $department,
        private string $category,
        private int $amountMinor,
        private DateTimeImmutable $expenseDate,
        private string $paymentMethod,
        private ?string $proofPath,
        private ?string $description,
        private string $fundType,
        private ?int $reimbursedToStaffId,
        private readonly int $recordedByStaffId,
    ) {}

    public static function record(
        string $name,
        ?int $shopId,
        string $department,
        string $category,
        int $amountMinor,
        DateTimeImmutable $expenseDate,
        string $paymentMethod,
        ?string $proofPath,
        ?string $description,
        string $fundType,
        ?int $reimbursedToStaffId,
        int $recordedByStaffId,
    ): self {
        return new self(null, $name, $shopId, $department, $category, $amountMinor, $expenseDate, $paymentMethod, $proofPath, $description, $fundType, $reimbursedToStaffId, $recordedByStaffId);
    }

    public static function reconstitute(
        ExpenseId $id,
        string $name,
        ?int $shopId,
        string $department,
        string $category,
        int $amountMinor,
        DateTimeImmutable $expenseDate,
        string $paymentMethod,
        ?string $proofPath,
        ?string $description,
        string $fundType,
        ?int $reimbursedToStaffId,
        int $recordedByStaffId,
    ): self {
        return new self($id, $name, $shopId, $department, $category, $amountMinor, $expenseDate, $paymentMethod, $proofPath, $description, $fundType, $reimbursedToStaffId, $recordedByStaffId);
    }

    /** A correction after the fact — e.g. a mistyped amount or wrong category. The proof document (if any) is replaced separately, not here. */
    public function update(
        string $name,
        string $department,
        string $category,
        int $amountMinor,
        DateTimeImmutable $expenseDate,
        string $paymentMethod,
        ?string $description,
        string $fundType,
        ?int $reimbursedToStaffId,
    ): void {
        $this->name = $name;
        $this->department = $department;
        $this->category = $category;
        $this->amountMinor = $amountMinor;
        $this->expenseDate = $expenseDate;
        $this->paymentMethod = $paymentMethod;
        $this->description = $description;
        $this->fundType = $fundType;
        $this->reimbursedToStaffId = $reimbursedToStaffId;
    }

    /** Replacing an uploaded proof document — the old file (if any) is the caller's (Handler's) responsibility to remove from storage. */
    public function replaceProof(?string $proofPath): void
    {
        $this->proofPath = $proofPath;
    }

    public function id(): ?ExpenseId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function shopId(): ?int
    {
        return $this->shopId;
    }

    public function department(): string
    {
        return $this->department;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function expenseDate(): DateTimeImmutable
    {
        return $this->expenseDate;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function proofPath(): ?string
    {
        return $this->proofPath;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function fundType(): string
    {
        return $this->fundType;
    }

    public function reimbursedToStaffId(): ?int
    {
        return $this->reimbursedToStaffId;
    }

    public function recordedByStaffId(): int
    {
        return $this->recordedByStaffId;
    }
}
