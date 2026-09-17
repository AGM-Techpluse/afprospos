<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Warranty;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Exact template: CustomerCannotViewAnotherCustomersOrderTest. */
class CustomerCannotViewAnotherCustomersClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_view_another_customers_warranty_claim(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $claim = WarrantyClaimRecord::factory()->create(['customer_id' => $owner->id]);

        $response = $this->actingAs($intruder, 'customer')->get("/customer/warranty/claims/{$claim->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('warranty_claims', ['id' => $claim->id, 'customer_id' => $owner->id]);
    }
}
