<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Entities;

use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\InvoiceNumber;
use Domain\Sales\Domain\ValueObjects\SaleId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * A finalized sale (DBDD §13.4) — deliberately immutable and separate
 * from the mutable checkout it was created from. No mutators, matching
 * the schema's own "deliberately no updated_at."
 */
final class Sale
{
    private function __construct(
        private readonly ?SaleId $id,
        private readonly CheckoutId $checkoutId,
        private readonly ShopId $shopId,
        private readonly ?CustomerId $customerId,
        private readonly StaffId $cashierStaffId,
        private readonly Money $total,
        private readonly ?int $paymentTransactionId,
        private readonly string $paymentMethod,
        private readonly ?string $paymentReference,
        private readonly InvoiceNumber $invoiceNumber,
    ) {}

    public static function fromPaidCheckout(
        CheckoutId $checkoutId,
        ShopId $shopId,
        ?CustomerId $customerId,
        StaffId $cashierStaffId,
        Money $total,
        string $paymentMethod,
        ?string $paymentReference,
        InvoiceNumber $invoiceNumber,
    ): self {
        return new self(null, $checkoutId, $shopId, $customerId, $cashierStaffId, $total, null, $paymentMethod, $paymentReference, $invoiceNumber);
    }

    public static function reconstitute(
        SaleId $id,
        CheckoutId $checkoutId,
        ShopId $shopId,
        ?CustomerId $customerId,
        StaffId $cashierStaffId,
        Money $total,
        ?int $paymentTransactionId,
        string $paymentMethod,
        ?string $paymentReference,
        InvoiceNumber $invoiceNumber,
    ): self {
        return new self($id, $checkoutId, $shopId, $customerId, $cashierStaffId, $total, $paymentTransactionId, $paymentMethod, $paymentReference, $invoiceNumber);
    }

    public function id(): ?SaleId
    {
        return $this->id;
    }

    public function checkoutId(): CheckoutId
    {
        return $this->checkoutId;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function customerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function cashierStaffId(): StaffId
    {
        return $this->cashierStaffId;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function paymentTransactionId(): ?int
    {
        return $this->paymentTransactionId;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function paymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function invoiceNumber(): InvoiceNumber
    {
        return $this->invoiceNumber;
    }
}
