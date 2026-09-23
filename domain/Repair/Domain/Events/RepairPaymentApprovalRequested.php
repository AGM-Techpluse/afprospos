<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

/** REP-BR-28: a completed repair's payment recording now needs Shop Owner approval. */
final readonly class RepairPaymentApprovalRequested
{
    public function __construct(
        public int $repairJobId,
        public int $shopId,
        public int $requestedByStaffId,
        public CarbonImmutable $occurredAt,
    ) {}
}
