<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Domain\Sales\Domain\Events\SaleCompleted;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SaleCompletedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_completed_creates_a_queued_notification_event_for_a_registered_customer(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id, 'invoice_number' => 'INV-TST-000123']);

        Event::dispatch(new SaleCompleted($sale->id, $customer->id, CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'SaleCompleted',
            'source_module' => 'Sales',
            'source_id' => $sale->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
        ]);
    }

    public function test_a_walk_in_sale_with_no_customer_creates_no_notification(): void
    {
        $sale = SaleRecord::factory()->create(['customer_id' => null]);

        Event::dispatch(new SaleCompleted($sale->id, null, CarbonImmutable::now()));

        $this->assertDatabaseMissing('notification_events', ['source_module' => 'Sales', 'source_id' => $sale->id]);
    }
}
