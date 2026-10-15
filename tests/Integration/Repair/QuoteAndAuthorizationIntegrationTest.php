<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\SendToQuoteCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\SendToQuoteHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Domain\Exceptions\DiagnosisNotRepairable;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisConclusionRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Staff send a saved repairable diagnosis to quote; the manager sets the quote, the down payment and any agreement in one action. */
class QuoteAndAuthorizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_diagnosis_cannot_be_sent_to_quote_without_a_saved_repairable_outcome(): void
    {
        $staff = StaffRecord::factory()->create();
        $jobId = $this->job('diagnosing');

        $this->expectException(DiagnosisNotRepairable::class);

        app(SendToQuoteHandler::class)->handle(new SendToQuoteCommand($jobId, $staff->id));
    }

    public function test_a_saved_repairable_diagnosis_can_be_sent_to_quote(): void
    {
        $staff = StaffRecord::factory()->create();
        $jobId = $this->job('diagnosing');
        $this->conclude($jobId, 'repairable', $staff->id);

        app(SendToQuoteHandler::class)->handle(new SendToQuoteCommand($jobId, $staff->id));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'awaiting_authorization']);
    }

    public function test_authorizing_sets_the_quote_records_the_agreement_and_starts_the_repair(): void
    {
        $manager = StaffRecord::factory()->create();
        $jobId = $this->job('awaiting_authorization');

        app(AuthorizeRepairHandler::class)->handle(new AuthorizeRepairCommand(
            repairJobId: $jobId,
            downPaymentRequiredMinor: 0,
            authorizedByStaffId: $manager->id,
            repairCostMinor: 1500000,
            specialAgreementNote: 'Warranty extended 3 months as a goodwill gesture',
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'in_progress', 'repair_cost_minor' => 1500000]);
        $this->assertDatabaseHas('repair_agreements', [
            'repair_job_id' => $jobId,
            'note' => 'Warranty extended 3 months as a goodwill gesture',
        ]);
    }

    public function test_authorizing_can_adjust_the_diagnosis_fee_in_the_same_action(): void
    {
        $manager = StaffRecord::factory()->create();
        $jobId = $this->job('awaiting_authorization');
        app(SetDiagnosisFeeHandler::class)->handle(
            new SetDiagnosisFeeCommand($jobId, 500000, $manager->id),
        );

        app(AuthorizeRepairHandler::class)->handle(new AuthorizeRepairCommand(
            repairJobId: $jobId,
            downPaymentRequiredMinor: 0,
            authorizedByStaffId: $manager->id,
            repairCostMinor: 1500000,
            diagnosisFeeMinor: 750000,
        ));

        $this->assertDatabaseHas('repair_diagnosis_fees', ['repair_job_id' => $jobId, 'amount_minor' => 750000, 'decision' => 'approved']);
        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'in_progress']);
    }

    public function test_a_down_payment_requirement_still_starts_the_repair(): void
    {
        $manager = StaffRecord::factory()->create();
        $jobId = $this->job('awaiting_authorization');

        app(AuthorizeRepairHandler::class)->handle(new AuthorizeRepairCommand(
            repairJobId: $jobId,
            downPaymentRequiredMinor: 500000,
            authorizedByStaffId: $manager->id,
            repairCostMinor: 1500000,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'in_progress', 'down_payment_required_minor' => 500000]);
        $this->assertDatabaseMissing('repair_agreements', ['repair_job_id' => $jobId]);
    }

    public function test_a_cashier_cannot_authorize_or_set_the_quote(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $jobId = $this->job('awaiting_authorization', $shop->id);

        $this->actingAs($cashier, 'staff')
            ->post("/admin/repairs/{$jobId}/authorize", ['down_payment_required_minor' => 0, 'repair_cost_minor' => 1500000])
            ->assertForbidden();
        $this->actingAs($cashier, 'staff')
            ->post("/admin/repairs/{$jobId}/repair-cost", ['repair_cost_minor' => 1500000])
            ->assertForbidden();

        $this->assertDatabaseHas('repair_jobs', ['id' => $jobId, 'repair_status' => 'awaiting_authorization', 'repair_cost_minor' => null]);
    }

    private function conclude(int $jobId, string $outcome, int $staffId): void
    {
        DiagnosisConclusionRecord::query()->create([
            'repair_job_id' => $jobId,
            'root_cause' => 'Cracked screen',
            'outcome' => $outcome,
            'diagnosed_by_staff_id' => $staffId,
        ]);
    }

    private function job(string $status, ?int $shopId = null): int
    {
        return RepairJobRecord::factory()->create([
            'shop_id' => $shopId ?? ShopRecord::factory()->create()->id,
            'customer_id' => CustomerRecord::factory()->create()->id,
            'repair_status' => $status,
            'repair_cost_minor' => null,
        ])->id;
    }
}
