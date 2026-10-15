<?php

declare(strict_types=1);

namespace Tests\Integration\Commission;

use Database\Seeders\RolePermissionSeeder;
use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;
use Domain\Commission\Application\Handlers\RecordCommissionEarnedHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> real repository -> real DB, no HTTP (CPNC §6's Integration tier). */
class RecordCommissionEarnedIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_flat_amount_as_a_fully_earned_entry(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Technician');

        app(RecordCommissionEarnedHandler::class)->handle(new RecordCommissionEarnedCommand(
            staffId: $staff->id,
            sourceType: 'repair',
            sourceId: 42,
            amountMinor: 50000,
            shopId: $shop->id,
        ));

        $this->assertDatabaseHas('commission_ledger_entries', [
            'staff_id' => $staff->id,
            'source_type' => 'repair',
            'source_id' => 42,
            'commissionable_amount_minor' => 50000,
            'rate_snapshot_percent' => 100.00,
            'role_snapshot' => 'Technician',
            'entry_type' => 'earned',
            'status' => 'earned',
            'shop_id' => $shop->id,
        ]);
    }

    public function test_records_unknown_role_when_staff_has_no_role_assigned(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        app(RecordCommissionEarnedHandler::class)->handle(new RecordCommissionEarnedCommand(
            staffId: $staff->id,
            sourceType: 'sale',
            sourceId: 7,
            amountMinor: 1000,
            shopId: $shop->id,
        ));

        $this->assertDatabaseHas('commission_ledger_entries', [
            'staff_id' => $staff->id,
            'role_snapshot' => 'Unknown',
        ]);
    }
}
