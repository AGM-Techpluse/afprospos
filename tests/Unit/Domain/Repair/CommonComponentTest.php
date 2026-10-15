<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Entities\CommonComponent;
use PHPUnit\Framework\TestCase;

class CommonComponentTest extends TestCase
{
    public function test_create_starts_with_no_id_and_the_given_attributes(): void
    {
        $component = CommonComponent::create('Battery', 2, 50000);

        $this->assertNull($component->id());
        $this->assertSame('Battery', $component->label());
        $this->assertSame(2, $component->sortOrder());
        $this->assertSame(50000, $component->commissionAmountMinor());
    }

    public function test_create_defaults_to_not_commissionable(): void
    {
        $component = CommonComponent::create('Battery', 0);

        $this->assertNull($component->commissionAmountMinor());
    }

    public function test_rename_reorder_and_set_commission_amount_mutate_in_place(): void
    {
        $component = CommonComponent::create('Battery', 0);

        $component->rename('Battery pack');
        $component->reorder(3);
        $component->setCommissionAmount(75000);

        $this->assertSame('Battery pack', $component->label());
        $this->assertSame(3, $component->sortOrder());
        $this->assertSame(75000, $component->commissionAmountMinor());
    }

    public function test_set_commission_amount_can_clear_it_back_to_null(): void
    {
        $component = CommonComponent::create('Battery', 0, 50000);

        $component->setCommissionAmount(null);

        $this->assertNull($component->commissionAmountMinor());
    }
}
