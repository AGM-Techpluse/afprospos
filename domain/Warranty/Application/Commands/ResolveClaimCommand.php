<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/**
 * For the `repair` remedy, `remedyReferenceId` is an existing
 * `repair_jobs.id` (validated via RepairJobLookup) — staff associates
 * an already-created repair job rather than this Command creating one
 * (WAR-BR-07 says "create or associate"; this slice implements
 * "associate" only). For `replace`, it's a `skus.id` (validated via
 * InventoryCatalogQuery) — reference-only, no automatic reservation.
 * `refund` does not go through this Command; see ApproveRefundCommand.
 */
final readonly class ResolveClaimCommand
{
    public function __construct(
        public int $warrantyClaimId,
        public int $remedyReferenceId,
        public int $resolvedByStaffId,
    ) {}
}
