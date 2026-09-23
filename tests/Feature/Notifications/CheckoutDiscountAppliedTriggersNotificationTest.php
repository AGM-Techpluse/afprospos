<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Domain\Events\CheckoutDiscountApplied;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** MKT-BR-15: every Shop Owner is notified when a discount needs approval. */
class CheckoutDiscountAppliedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_shop_owner_gets_a_queued_staff_notification(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        Event::dispatch(new CheckoutDiscountApplied(1, 1, 1000, $cashier->id, CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'CheckoutDiscountApplied',
            'source_module' => 'Sales',
            'source_id' => 1,
            'recipient_type' => 'staff',
            'recipient_id' => $owner->id,
        ]);
        $this->assertDatabaseMissing('notification_events', [
            'event_type' => 'CheckoutDiscountApplied',
            'recipient_type' => 'staff',
            'recipient_id' => $cashier->id,
        ]);
    }
}
