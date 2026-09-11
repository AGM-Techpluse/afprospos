<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\DTOs;

/** Per-row outcome for the Import Results page — one bad row must not roll back the whole batch. */
final readonly class ProductImportRowResult
{
    public function __construct(
        public int $rowNumber,
        public bool $succeeded,
        public ?string $skuCode,
        public ?string $errorMessage,
    ) {}
}
