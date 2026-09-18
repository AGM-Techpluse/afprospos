<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;

/** Backs Admin and Customer Return Show pages — enriches the return with its originating sale (including `payment_transaction_id`, so staff can copy it into the refund form) via Sales' published SaleLookup contract, never its Application\Queries directly (CPNC §4.2). Mirrors WarrantyClaimDetailQuery. */
final class ReturnRequestDetailQuery
{
    public function __construct(private readonly SaleLookup $saleLookup) {}

    /** @return array<string, mixed>|null */
    public function find(int $returnRequestId): ?array
    {
        $returnRequest = ReturnRequestRecord::query()->with('customer')->find($returnRequestId);

        if ($returnRequest === null) {
            return null;
        }

        return [
            'id' => $returnRequest->id,
            'sale' => $this->saleLookup->find($returnRequest->sale_id),
            'customer_id' => $returnRequest->customer_id,
            'customer' => $returnRequest->customer === null ? null : [
                'name' => $returnRequest->customer->name,
                'phone' => $returnRequest->customer->phone,
            ],
            'resolution_state' => $returnRequest->resolution_state,
            'return_window_expires_at' => $returnRequest->return_window_expires_at->toIso8601String(),
            'denial_reason' => $returnRequest->denial_reason,
            'override_approved_by_staff_id' => $returnRequest->override_approved_by_staff_id,
            'refund_transaction_id' => $returnRequest->refund_transaction_id,
            'created_at' => $returnRequest->created_at->toIso8601String(),
        ];
    }
}
