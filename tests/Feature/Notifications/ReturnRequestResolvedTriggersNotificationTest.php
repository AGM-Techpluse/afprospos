<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Domain\Warranty\Domain\Events\ReturnRequestResolved;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReturnRequestResolvedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_request_resolved_creates_a_queued_notification_event_for_the_customer(): void
    {
        $returnRequest = ReturnRequestRecord::factory()->create(['resolution_state' => 'resolved']);

        Event::dispatch(new ReturnRequestResolved($returnRequest->id, $returnRequest->customer_id, CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'ReturnRequestResolved',
            'source_module' => 'Warranty',
            'source_id' => $returnRequest->id,
            'recipient_type' => 'customer',
            'recipient_id' => $returnRequest->customer_id,
        ]);
    }
}
