<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairPaymentApproval;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPaymentApprovalId;

interface RepairPaymentApprovalRepository
{
    public function get(RepairPaymentApprovalId $id): RepairPaymentApproval;

    public function lockForUpdate(RepairPaymentApprovalId $id): RepairPaymentApproval;

    public function save(RepairPaymentApproval $approval): RepairPaymentApprovalId;

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairPaymentApproval;
}
