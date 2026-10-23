<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Entities\RepairPartReservation;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPartReservationId;
use PHPUnit\Framework\TestCase;

class RepairPartReservationTest extends TestCase
{
    public function test_reserve_stores_the_snapshotted_unit_price(): void
    {
        $reservation = RepairPartReservation::reserve(new RepairJobId(1), null, 10, 2, 150000);

        $this->assertSame(150000, $reservation->unitPriceMinor());
        $this->assertSame('reserved', $reservation->status());
    }

    public function test_reconstitute_round_trips_the_unit_price(): void
    {
        $reservation = RepairPartReservation::reconstitute(
            new RepairPartReservationId(5),
            new RepairJobId(1),
            null,
            10,
            2,
            150000,
            'installed',
        );

        $this->assertSame(150000, $reservation->unitPriceMinor());
        $this->assertSame('installed', $reservation->status());
    }
}
