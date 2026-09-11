<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\DTOs;

final readonly class InvariantViolation
{
    public function __construct(
        public string $subjectType,
        public int $subjectId,
        public string $description,
    ) {}
}
