<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Contracts;

/**
 * The published cross-module read contract for validating a warranty
 * claim's source repair (WAR-BR-02/03) — Warranty depends on this, and
 * only this, never on Repair's Domain/Infrastructure internals directly
 * (CPNC §4.2, same shape as Sales' SaleLookup).
 */
interface RepairJobLookup
{
    /**
     * @return array{
     *     id: int,
     *     shop_id: int,
     *     customer_id: int,
     *     device_make: string,
     *     device_model: string,
     *     repair_status: string,
     *     created_at: string,
     * }|null
     */
    public function find(int $repairJobId): ?array;
}
