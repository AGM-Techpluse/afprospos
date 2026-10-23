<?php

declare(strict_types=1);

namespace Tests\Integration\Identity;

use Domain\Identity\Application\Handlers\BackfillWalkInCustomersHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillWalkInCustomersHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_customer_record_per_distinct_phone_across_tables_and_leaves_historical_rows_untouched(): void
    {
        $repair = RepairJobRecord::factory()->create([
            'customer_id' => null,
            'walk_in_name' => 'Bisi Lawal',
            'walk_in_phone' => '08077778888',
            'walk_in_email' => null,
        ]);

        // Same phone on a different table — should collapse to one customer, not two.
        SalesCheckoutRecord::factory()->create([
            'customer_id' => null,
            'walk_in_name' => 'Bisi Lawal',
            'walk_in_phone' => '08077778888',
            'walk_in_email' => 'bisi@example.com',
        ]);

        $result = app(BackfillWalkInCustomersHandler::class)->handle();

        $this->assertSame(1, $result['found']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, CustomerRecord::query()->where('phone', '08077778888')->count());

        // Historical rows are deliberately left untouched — repointing an already-settled record risks changing unaudited business logic elsewhere.
        $this->assertNull($repair->fresh()->customer_id);
    }

    public function test_running_it_twice_creates_nothing_new(): void
    {
        RepairJobRecord::factory()->create([
            'customer_id' => null,
            'walk_in_name' => 'Repeat Visitor',
            'walk_in_phone' => '08022223333',
        ]);

        $handler = app(BackfillWalkInCustomersHandler::class);
        $first = $handler->handle();
        $second = $handler->handle();

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, CustomerRecord::query()->where('phone', '08022223333')->count());
    }
}
