<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use Domain\Inventory\Domain\Services\SkuGenerator;
use PHPUnit\Framework\TestCase;

class SkuGeneratorTest extends TestCase
{
    public function test_generates_the_documented_format(): void
    {
        // DBDD §14.2's format is [ShopCode]-[CategoryCode]-[Sequence];
        // its illustrative "LGS-PHN-000482" uses a curated abbreviation
        // for "Phones" that isn't derivable by any documented rule (not
        // first-N-letters, not consonants-only) — categories are
        // free-text (BRD INV-03), so this generator derives the code
        // deterministically (first 3 letters) rather than requiring a
        // maintained per-category abbreviation table.
        $this->assertSame('LGS-PHO-000482', SkuGenerator::generate('LGS', 'Phones', 482));
    }

    public function test_uppercases_and_truncates_category_to_three_letters(): void
    {
        $this->assertSame('MAIN-ACC-000001', SkuGenerator::generate('main', 'accessories', 1));
    }
}
