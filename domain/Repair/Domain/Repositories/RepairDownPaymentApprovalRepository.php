<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairDownPaymentApproval;
use Domain\Repair\Domain\ValueObjects\RepairDownPaymentApprovalId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

interface RepairDownPaymentApprovalRepository
{
    public function get(RepairDownPaymentApprovalId $id): RepairDownPaymentApproval;

    public function lockForUpdate(RepairDownPaymentApprovalId $id): RepairDownPaymentApproval;

    public function save(RepairDownPaymentApproval $approval): RepairDownPaymentApprovalId;

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairDownPaymentApproval;
}
