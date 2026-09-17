<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Repositories;

use Domain\Warranty\Domain\Entities\WarrantyPolicy;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;

final class EloquentWarrantyPolicyRepository implements WarrantyPolicyRepository
{
    public function get(WarrantyPolicyId $id): WarrantyPolicy
    {
        return $this->toDomain(WarrantyPolicyRecord::query()->findOrFail($id->value));
    }

    public function save(WarrantyPolicy $policy): WarrantyPolicyId
    {
        $attributes = [
            'name' => $policy->name(),
            'coverage_duration_days' => $policy->coverageDurationDays(),
            'coverage_start_point' => $policy->coverageStartPoint(),
            'covered_scope' => $policy->coveredScope(),
            'exclusions' => $policy->exclusions(),
            'available_remedies' => $policy->availableRemedies(),
            'coverage_extent' => $policy->coverageExtent(),
        ];

        if ($policy->id() === null) {
            $record = WarrantyPolicyRecord::query()->create($attributes);
        } else {
            $record = WarrantyPolicyRecord::query()->findOrFail($policy->id()->value);
            $record->update($attributes);
        }

        return new WarrantyPolicyId($record->id);
    }

    public function delete(WarrantyPolicyId $id): void
    {
        WarrantyPolicyRecord::query()->findOrFail($id->value)->delete();
    }

    private function toDomain(WarrantyPolicyRecord $record): WarrantyPolicy
    {
        return WarrantyPolicy::reconstitute(
            new WarrantyPolicyId($record->id),
            $record->name,
            $record->coverage_duration_days,
            $record->coverage_start_point,
            $record->covered_scope,
            $record->exclusions,
            $record->available_remedies,
            $record->coverage_extent,
        );
    }
}
