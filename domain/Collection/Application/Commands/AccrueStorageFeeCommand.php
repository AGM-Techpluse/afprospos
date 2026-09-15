<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

final readonly class AccrueStorageFeeCommand
{
    public function __construct(
        public int $collectionCaseId,
    ) {}
}
