<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Domain\Warranty\Domain\Events\TradeInCreditApplied;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TradeInCreditAppliedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trade_in_credit_applied_creates_a_queued_notification_event_for_the_customer(): void
    {
        $tradeIn = TradeInAssessmentRecord::factory()->create(['resolution_state' => 'applied']);

        Event::dispatch(new TradeInCreditApplied($tradeIn->id, $tradeIn->customer_id, CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'TradeInCreditApplied',
            'source_module' => 'Warranty',
            'source_id' => $tradeIn->id,
            'recipient_type' => 'customer',
            'recipient_id' => $tradeIn->customer_id,
        ]);
    }
}
