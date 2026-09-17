<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/**
 * DBDD §15.2 has no free-text "reported issue" column on `warranty_claims`
 * — the customer's fault description is captured verbally/in person and
 * recorded by staff as `assessment_notes` during AssessClaimCommand, not
 * at submission time. Submission only records the structural links: which
 * policy, which origin, which customer.
 */
final readonly class CreateWarrantyClaimCommand
{
    public function __construct(
        public int $warrantyPolicyId,
        public ?int $originatingSaleId,
        public ?int $originatingRepairJobId,
        public int $customerId,
        public ?int $submittedByStaffId,
    ) {}
}
