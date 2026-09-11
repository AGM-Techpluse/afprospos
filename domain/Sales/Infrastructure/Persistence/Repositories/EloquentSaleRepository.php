<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Repositories;

use Domain\Sales\Domain\Entities\Sale;
use Domain\Sales\Domain\Repositories\SaleRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\InvoiceNumber;
use Domain\Sales\Domain\ValueObjects\SaleId;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentSaleRepository implements SaleRepository
{
    public function get(SaleId $id): Sale
    {
        $record = SaleRecord::query()->findOrFail($id->value);

        return $this->toDomain($record);
    }

    public function save(Sale $sale): SaleId
    {
        $record = SaleRecord::query()->create([
            'sales_checkout_id' => $sale->checkoutId()->value,
            'shop_id' => $sale->shopId()->value,
            'customer_id' => $sale->customerId()?->value,
            'cashier_staff_id' => $sale->cashierStaffId()->value,
            'total_minor' => $sale->total()->minor,
            'payment_transaction_id' => $sale->paymentTransactionId(),
            'payment_method' => $sale->paymentMethod(),
            'payment_reference' => $sale->paymentReference(),
            'invoice_number' => $sale->invoiceNumber()->value,
        ]);

        return new SaleId($record->id);
    }

    /** Same unprotected count+1 shape as SkuRepository::nextSequence — accepted precedent, not a hot concurrent path. */
    public function nextInvoiceSequence(int $shopId): int
    {
        return SaleRecord::query()->where('shop_id', $shopId)->count() + 1;
    }

    private function toDomain(SaleRecord $record): Sale
    {
        return Sale::reconstitute(
            new SaleId($record->id),
            new CheckoutId($record->sales_checkout_id),
            new ShopId($record->shop_id),
            $record->customer_id !== null ? new CustomerId($record->customer_id) : null,
            new StaffId($record->cashier_staff_id),
            new Money($record->total_minor),
            $record->payment_transaction_id,
            $record->payment_method,
            $record->payment_reference,
            new InvoiceNumber($record->invoice_number),
        );
    }
}
