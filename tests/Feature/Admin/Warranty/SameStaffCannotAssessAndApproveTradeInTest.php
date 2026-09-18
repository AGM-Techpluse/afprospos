<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Application\Commands\AssessTradeInCommand;
use Domain\Warranty\Application\Handlers\AssessTradeInHandler;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** TRADE-BR-05: "Assessed trade-in values shall require authorization ... separate from the staff member who performed the assessment." A "Cannot" test asserts both the rejection and that nothing about the record changed. */
class SameStaffCannotAssessAndApproveTradeInTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_staff_member_who_assessed_a_trade_in_cannot_also_approve_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Technician');

        $tradeIn = TradeInAssessmentRecord::factory()->create();

        app(AssessTradeInHandler::class)->handle(new AssessTradeInCommand(
            tradeInAssessmentId: $tradeIn->id,
            assessedValueMinor: 35000000,
            assessedByStaffId: $staff->id,
        ));

        $response = $this->actingAs($staff, 'staff')->post("/admin/warranty/trade-ins/{$tradeIn->id}/approve");

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('trade_in_assessments', ['id' => $tradeIn->id, 'resolution_state' => 'assessed']);
    }
}
