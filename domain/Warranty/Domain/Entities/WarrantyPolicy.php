<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Entities;

use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;

/** DBDD §15.1 — a configured policy, not a yes/no flag on a sale (BLD §5.3). Admin-editable, no lifecycle state machine. */
final class WarrantyPolicy
{
    private function __construct(
        private readonly ?WarrantyPolicyId $id,
        private string $name,
        private int $coverageDurationDays,
        private string $coverageStartPoint,
        private array $coveredScope,
        private array $exclusions,
        private array $availableRemedies,
        private string $coverageExtent,
    ) {}

    public static function create(
        string $name,
        int $coverageDurationDays,
        string $coverageStartPoint,
        array $coveredScope,
        array $exclusions,
        array $availableRemedies,
        string $coverageExtent,
    ): self {
        return new self(null, $name, $coverageDurationDays, $coverageStartPoint, $coveredScope, $exclusions, $availableRemedies, $coverageExtent);
    }

    public static function reconstitute(
        WarrantyPolicyId $id,
        string $name,
        int $coverageDurationDays,
        string $coverageStartPoint,
        array $coveredScope,
        array $exclusions,
        array $availableRemedies,
        string $coverageExtent,
    ): self {
        return new self($id, $name, $coverageDurationDays, $coverageStartPoint, $coveredScope, $exclusions, $availableRemedies, $coverageExtent);
    }

    public function update(
        string $name,
        int $coverageDurationDays,
        string $coverageStartPoint,
        array $coveredScope,
        array $exclusions,
        array $availableRemedies,
        string $coverageExtent,
    ): void {
        $this->name = $name;
        $this->coverageDurationDays = $coverageDurationDays;
        $this->coverageStartPoint = $coverageStartPoint;
        $this->coveredScope = $coveredScope;
        $this->exclusions = $exclusions;
        $this->availableRemedies = $availableRemedies;
        $this->coverageExtent = $coverageExtent;
    }

    public function id(): ?WarrantyPolicyId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function coverageDurationDays(): int
    {
        return $this->coverageDurationDays;
    }

    public function coverageStartPoint(): string
    {
        return $this->coverageStartPoint;
    }

    public function coveredScope(): array
    {
        return $this->coveredScope;
    }

    public function exclusions(): array
    {
        return $this->exclusions;
    }

    public function availableRemedies(): array
    {
        return $this->availableRemedies;
    }

    public function coverageExtent(): string
    {
        return $this->coverageExtent;
    }
}
