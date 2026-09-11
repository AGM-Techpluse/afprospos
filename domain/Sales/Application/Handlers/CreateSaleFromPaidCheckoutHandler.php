<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Domain\Entities\Sale;
use Domain\Sales\Domain\Repositories\SaleRepository;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\Services\InvoiceNumberGenerator;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\InvoiceNumber;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * The sale-completion boundary (ADD §16): a successful payment does
 * not independently "sell" inventory — this Handler is the one
 * coordinating service. Covers Phase 4's two completion paths (cash-
 * authorized immediate, or later staff-confirmed remote/bank-transfer)
 * identically: lock the checkout, verify it can still be paid (else
 * SALE/PAY-BR-08's exception path), consume every reservation, write
 * the immutable Sale.
 */
final class CreateSaleFromPaidCheckoutHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly SaleRepository $sales,
        private readonly InventoryReservationService $reservations,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateSaleFromPaidCheckoutCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $checkoutId = new CheckoutId($command->checkoutId);
            $checkout = $this->checkouts->lockForUpdate($checkoutId);

            $checkout->markPaid(CarbonImmutable::now());

            foreach ($checkout->items() as $item) {
                $this->reservations->consume(new ConsumeInventoryCommand(
                    skuId: $item->skuId(),
                    shopId: $checkout->shopId()->value,
                    quantity: $item->quantity(),
                    sourceType: 'checkout',
                    sourceId: $command->checkoutId,
                ));
            }

            $this->checkouts->save($checkout);

            $sequence = $this->sales->nextInvoiceSequence($checkout->shopId()->value);
            $invoiceNumber = new InvoiceNumber(InvoiceNumberGenerator::generate($command->shopCode, $sequence));

            $sale = Sale::fromPaidCheckout(
                $checkoutId,
                $checkout->shopId(),
                $checkout->customerId(),
                new StaffId($command->confirmedByStaffId),
                new Money($checkout->totalMinor()),
                $command->paymentMethod,
                $command->paymentReference,
                $invoiceNumber,
            );

            $saleId = $this->sales->save($sale);

            $this->audit->record(
                module: 'Sales',
                eventType: 'SaleCompleted',
                actorStaffId: new StaffId($command->confirmedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sales_sale',
                subjectId: $saleId->value,
                beforeState: null,
                afterState: [
                    'checkout_id' => $command->checkoutId,
                    'total_minor' => $checkout->totalMinor(),
                    'payment_method' => $command->paymentMethod,
                    'invoice_number' => $invoiceNumber->value,
                ],
            );

            return $saleId->value;
        });
    }
}
