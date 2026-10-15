<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Application\Commands\RequestDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Handlers\RequestDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The repair page shows the diagnosis fee's decision and payment, and adds an unpaid approved fee to the quote total. */
class RepairDetailShowsDiagnosisFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_set_unpaid_fee_is_approved_and_added_to_the_quote(): void
    {
        $staff = StaffRecord::factory()->create();
        $jobId = $this->job('diagnosing', 1000000);

        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $staff->id));

        $detail = app(RepairJobDetailQuery::class)->find($jobId);

        $this->assertSame('approved', $detail['diagnosis_fee']['decision']);
        $this->assertFalse($detail['diagnosis_fee']['paid']);
        $this->assertSame(500000, $detail['diagnosis_fee']['outstanding_minor']);
        $this->assertSame(1500000, $detail['quote_total_minor']);
    }

    public function test_an_approved_unpaid_fee_is_added_to_the_quote_total(): void
    {
        $staff = StaffRecord::factory()->create();
        $jobId = $this->job('awaiting_authorization', 1000000);
        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $staff->id));

        $detail = app(RepairJobDetailQuery::class)->find($jobId);

        $this->assertSame(500000, $detail['diagnosis_fee']['outstanding_minor']);
        $this->assertSame(1500000, $detail['quote_total_minor']);
    }

    public function test_a_paid_fee_is_shown_as_paid_and_left_out_of_the_quote(): void
    {
        $staff = StaffRecord::factory()->create();
        $jobId = $this->job('diagnosing', 1000000);
        app(SetDiagnosisFeeHandler::class)->handle(new SetDiagnosisFeeCommand($jobId, 500000, $staff->id));

        // Cash is confirmed at the counter, so it is paid as soon as it is collected.
        app(RequestDiagnosisFeeHandler::class)->handle(new RequestDiagnosisFeeCommand($jobId, 'cash', $staff->id));

        $detail = app(RepairJobDetailQuery::class)->find($jobId);

        $this->assertTrue($detail['diagnosis_fee']['paid']);
        $this->assertSame(0, $detail['diagnosis_fee']['outstanding_minor']);
        $this->assertSame(1000000, $detail['quote_total_minor']);
    }

    public function test_a_repair_without_a_fee_shows_none(): void
    {
        $jobId = $this->job('diagnosing', null);

        $this->assertNull(app(RepairJobDetailQuery::class)->find($jobId)['diagnosis_fee']);
    }

    private function job(string $status, ?int $repairCostMinor): int
    {
        return RepairJobRecord::factory()->create([
            'shop_id' => ShopRecord::factory()->create()->id,
            'customer_id' => CustomerRecord::factory()->create()->id,
            'repair_status' => $status,
            'repair_cost_minor' => $repairCostMinor,
        ])->id;
    }
}
