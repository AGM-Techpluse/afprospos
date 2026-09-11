<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Sales;

use Domain\Sales\Domain\Services\InvoiceNumberGenerator;
use PHPUnit\Framework\TestCase;

class InvoiceNumberGeneratorTest extends TestCase
{
    public function test_generates_the_expected_format(): void
    {
        $this->assertSame('INV-LGS-000001', InvoiceNumberGenerator::generate('lgs', 1));
        $this->assertSame('INV-LGS-000482', InvoiceNumberGenerator::generate('LGS', 482));
    }
}
