<?php

declare(strict_types=1);

namespace Tests\Integration\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Application\Commands\CreateReturnRequestCommand;
use Domain\Warranty\Application\Handlers\CreateReturnRequestHandler;
use Domain\Warranty\Domain\Exceptions\ReturnDoesNotBelongToCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateReturnRequestIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_return_request_snapshots_the_return_window_from_the_sale_date(): void
    {
        config(['afprospos.return_window_days' => 14]);

        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id, 'created_at' => now()->subDays(3)]);

        $id = app(CreateReturnRequestHandler::class)->handle(new CreateReturnRequestCommand(
            saleId: $sale->id,
            customerId: $customer->id,
            submittedByStaffId: null,
        ));

        $this->assertDatabaseHas('return_requests', [
            'id' => $id,
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'requested',
        ]);

        $returnRequest = DB::table('return_requests')->find($id);
        $expectedWindow = $sale->created_at->copy()->addDays(14);
        $this->assertSame($expectedWindow->toDateString(), Carbon::parse($returnRequest->return_window_expires_at)->toDateString());
    }

    public function test_a_customer_cannot_request_a_return_for_another_customers_sale(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);

        $this->expectException(ReturnDoesNotBelongToCustomer::class);

        try {
            app(CreateReturnRequestHandler::class)->handle(new CreateReturnRequestCommand(
                saleId: $sale->id,
                customerId: $intruder->id,
                submittedByStaffId: null,
            ));
        } finally {
            $this->assertDatabaseMissing('return_requests', ['sale_id' => $sale->id]);
        }
    }
}
