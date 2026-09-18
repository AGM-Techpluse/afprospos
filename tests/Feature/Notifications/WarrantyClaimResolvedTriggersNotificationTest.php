<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Domain\Warranty\Domain\Events\WarrantyClaimResolved;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Proves the outbox listener wiring, including the new WarrantyClaimLookup contract resolving customer_id (WarrantyClaimResolved doesn't carry one itself). */
class WarrantyClaimResolvedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warranty_claim_resolved_creates_a_queued_notification_event_for_the_customer(): void
    {
        $claim = WarrantyClaimRecord::factory()->create(['resolution_state' => 'resolved', 'selected_remedy' => 'refund']);

        Event::dispatch(new WarrantyClaimResolved($claim->id, 'refund', CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'WarrantyClaimResolved',
            'source_module' => 'Warranty',
            'source_id' => $claim->id,
            'recipient_type' => 'customer',
            'recipient_id' => $claim->customer_id,
            'category' => 'transactional',
        ]);
    }
}
