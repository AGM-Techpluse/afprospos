<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Commission\Domain\Entities;

use Domain\Commission\Domain\Entities\CommissionLedgerEntry;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CommissionLedgerEntryTest extends TestCase
{
    public function test_earn_sets_a_fully_realized_flat_rate_entry(): void
    {
        $entry = CommissionLedgerEntry::earn(
            new StaffId(1),
            'repair',
            42,
            new Money(50000),
            'Technician',
            new ShopId(1),
        );

        $this->assertNull($entry->id());
        $this->assertSame('repair', $entry->sourceType());
        $this->assertSame(42, $entry->sourceId());
        $this->assertSame(50000, $entry->commissionableAmount()->minor);
        $this->assertSame(100.00, $entry->rateSnapshotPercent());
        $this->assertSame('Technician', $entry->roleSnapshot());
        $this->assertSame('earned', $entry->entryType());
        $this->assertNull($entry->reversalOfEntryId());
        $this->assertSame('earned', $entry->status());
    }

    public function test_earn_rejects_an_unknown_source_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommissionLedgerEntry::earn(new StaffId(1), 'referral', 1, new Money(1000), 'Sales', new ShopId(1));
    }
}
