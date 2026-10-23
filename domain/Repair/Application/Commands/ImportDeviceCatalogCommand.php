<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

use Domain\Repair\Application\DTOs\DeviceCatalogImportRow;

/** Bulk CSV import. Parsing the uploaded file is a Controller-level concern (CPNC §2.3); this Command carries the already-parsed rows. */
final readonly class ImportDeviceCatalogCommand
{
    /** @param  DeviceCatalogImportRow[]  $rows */
    public function __construct(
        public array $rows,
        public int $importedByStaffId,
    ) {}
}
