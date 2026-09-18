<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Warranty;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A "Cannot" test asserts both the rejection and that nothing about the record changed, per CLAUDE.md's own rule. */
class CustomerCannotReturnAnotherCustomersSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_request_a_return_for_another_customers_sale(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);

        $response = $this->actingAs($intruder, 'customer')->post('/customer/warranty/returns', ['sale_id' => $sale->id]);

        $response->assertSessionHasErrors('sale_id');
        $this->assertDatabaseMissing('return_requests', ['sale_id' => $sale->id]);
    }

    public function test_viewing_another_customers_return_is_forbidden(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['customer_id' => $owner->id]);

        $this->actingAs($owner, 'customer')->post('/customer/warranty/returns', ['sale_id' => $sale->id]);
        $returnId = DB::table('return_requests')->latest('id')->value('id');

        $response = $this->actingAs($intruder, 'customer')->get("/customer/warranty/returns/{$returnId}");

        $response->assertForbidden();
    }
}
