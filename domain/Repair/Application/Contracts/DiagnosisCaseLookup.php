<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Contracts;

/**
 * The published cross-module read contract for resolving a diagnosis
 * case — e.g. Payments' reconciliation queue, which needs to describe a
 * `diagnosis_case` payable (the pre-repair-job diagnosis fee) by its
 * device and customer instead of showing a bare id (mirrors
 * RepairJobLookup's role for a `repair_job` payable, CPNC §4.2).
 */
interface DiagnosisCaseLookup
{
    /**
     * @return array{
     *     id: int,
     *     shop_id: int,
     *     customer_id: int|null,
     *     walk_in_name: string|null,
     *     device_make: string,
     *     device_model: string,
     *     status: string,
     *     created_at: string,
     * }|null
     */
    public function find(int $diagnosisCaseId): ?array;
}
