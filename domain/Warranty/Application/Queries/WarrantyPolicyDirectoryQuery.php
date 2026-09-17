<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;

/** Backs the Admin Policies list and the claim-creation policy dropdown on both Admin and Customer sides. */
final class WarrantyPolicyDirectoryQuery
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return WarrantyPolicyRecord::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (WarrantyPolicyRecord $policy): array => [
                'id' => $policy->id,
                'name' => $policy->name,
                'coverage_duration_days' => $policy->coverage_duration_days,
                'coverage_start_point' => $policy->coverage_start_point,
                'covered_scope' => $policy->covered_scope,
                'exclusions' => $policy->exclusions,
                'available_remedies' => $policy->available_remedies,
                'coverage_extent' => $policy->coverage_extent,
            ])->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $warrantyPolicyId): ?array
    {
        $policy = WarrantyPolicyRecord::query()->find($warrantyPolicyId);

        if ($policy === null) {
            return null;
        }

        return [
            'id' => $policy->id,
            'name' => $policy->name,
            'coverage_duration_days' => $policy->coverage_duration_days,
            'coverage_start_point' => $policy->coverage_start_point,
            'covered_scope' => $policy->covered_scope,
            'exclusions' => $policy->exclusions,
            'available_remedies' => $policy->available_remedies,
            'coverage_extent' => $policy->coverage_extent,
        ];
    }
}
