<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RepairJobRepairCostTest extends TestCase
{
    public function test_intake_allows_no_labour_charge(): void
    {
        $job = RepairJob::intake(new ShopId(1), new CustomerId(1), 'Apple', 'iPhone 15');

        $this->assertNull($job->labourCharge());
        $this->assertNull($job->repairCost());
    }

    #[DataProvider('editableStatuses')]
    public function test_repair_cost_can_be_set_in_editable_statuses(string $status): void
    {
        $job = $this->jobAt($status);

        $job->setRepairCost(new Money(1500000));

        $this->assertSame(1500000, $job->repairCost()?->minor);
    }

    #[DataProvider('nonEditableStatuses')]
    public function test_repair_cost_cannot_be_set_outside_editable_statuses(string $status): void
    {
        $job = $this->jobAt($status);

        $this->expectException(InvalidRepairTransition::class);

        $job->setRepairCost(new Money(1500000));
    }

    public static function editableStatuses(): array
    {
        return [
            'awaiting_authorization' => ['awaiting_authorization'],
            'payment_overdue' => ['payment_overdue'],
        ];
    }

    public static function nonEditableStatuses(): array
    {
        return [
            'received' => ['received'],
            'diagnosing' => ['diagnosing'],
            'unrepairable' => ['unrepairable'],
            // Once the manager has authorized the quote, its amount is fixed.
            'in_progress' => ['in_progress'],
            'awaiting_parts' => ['awaiting_parts'],
            'failed_requires_resolution' => ['failed_requires_resolution'],
            'qc_pending' => ['qc_pending'],
            'handover' => ['handover'],
            'completed' => ['completed'],
            'expired_cancelled' => ['expired_cancelled'],
        ];
    }

    private function jobAt(string $status): RepairJob
    {
        return RepairJob::reconstitute(
            id: new RepairJobId(1),
            shopId: new ShopId(1),
            customerId: new CustomerId(1),
            deviceMake: 'Apple',
            deviceModel: 'iPhone 15',
            reportedIssue: null,
            deviceImeiSerial: null,
            deviceLockType: 'none',
            deviceLockValue: null,
            technicianStaffId: null,
            labourCharge: new Money(500000),
            repairCost: null,
            downPaymentRequired: null,
            downPaymentDeadlineAt: null,
            status: $status,
            financialStatus: 'unpaid',
            estimatedCollectionDate: null,
            unrepairableSettlementState: null,
            resolutionNotes: null,
        );
    }
}
