<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerCanRequestReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_request_a_return_for_their_own_sale(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer, 'customer')->post('/customer/warranty/returns', ['sale_id' => $sale->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('return_requests', [
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'requested',
        ]);
    }

    public function test_a_customer_can_view_their_own_return(): void
    {
        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')->post('/customer/warranty/returns', ['sale_id' => $sale->id]);
        $returnId = DB::table('return_requests')->latest('id')->value('id');

        $response = $this->actingAs($customer, 'customer')->get("/customer/warranty/returns/{$returnId}");

        $response->assertOk();
    }
}
