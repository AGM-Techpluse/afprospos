<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

use Domain\Inventory\Application\DTOs\ProductImportRow;

/** BRD INV-01: bulk CSV import. Parsing the uploaded file is a Controller-level concern (CPNC §2.3 "validate input"); this Command carries the already-parsed rows. */
final readonly class ImportProductsCommand
{
    /** @param  ProductImportRow[]  $rows */
    public function __construct(
        public array $rows,
        public string $shopCode,
        public int $shopId,
        public int $importedByStaffId,
    ) {}
}
