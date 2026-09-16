<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemSuggestedSkuRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobProblemTagRecord;

/** Backs both the Settings catalog-management page and the repair intake picker — same nested shape either way. */
final class DeviceCatalogQuery
{
    public function __construct(private readonly InventoryCatalogQuery $catalog) {}

    /**
     * Lightweight shape for the repair-intake picker — types, brands, and
     * problem tags only, skipping the suggested-SKU lookups `full()` does
     * for the Settings page (intake has no shop-scoped part data to show).
     *
     * @return array<int, array{id:int, label:string, icon:string, brands: array<int, array{id:int, name:string}>, problem_tags: array<int, array{id:int, label:string}>}>
     */
    public function structure(): array
    {
        $brandsByType = DeviceBrandRecord::query()->orderBy('sort_order')->get()->groupBy('device_type_id');
        $tagsByType = DeviceProblemTagRecord::query()->orderBy('sort_order')->get()->groupBy('device_type_id');

        return DeviceTypeRecord::query()
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (DeviceTypeRecord $type): array => [
                'id' => $type->id,
                'label' => $type->label,
                'icon' => $type->icon,
                'brands' => ($brandsByType->get($type->id) ?? collect())
                    ->map(static fn (DeviceBrandRecord $brand): array => ['id' => $brand->id, 'name' => $brand->name])
                    ->values()->all(),
                'problem_tags' => ($tagsByType->get($type->id) ?? collect())
                    ->map(static fn (DeviceProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label])
                    ->values()->all(),
            ])->all();
    }

    /**
     * Parts commonly needed for a specific repair job — derived from the
     * problem tags it was created with (repair_job_problem_tags), not
     * from a device_type_id stored on the job itself (there isn't one);
     * the tag is the only link back into the catalog. A tag deleted from
     * the catalog since intake (device_problem_tag_id null) simply
     * contributes no suggestions, since there's nothing left to join to.
     *
     * @return array<int, array{sku_id:int, sku_code:?string, product_name:?string}>
     */
    public function suggestedPartsForRepair(int $repairJobId, int $shopId): array
    {
        $tagIds = RepairJobProblemTagRecord::query()
            ->where('repair_job_id', $repairJobId)
            ->whereNotNull('device_problem_tag_id')
            ->pluck('device_problem_tag_id');

        if ($tagIds->isEmpty()) {
            return [];
        }

        $skuIds = DeviceProblemSuggestedSkuRecord::query()
            ->whereIn('device_problem_tag_id', $tagIds)
            ->pluck('sku_id')
            ->unique();

        return $skuIds
            ->map(function (int $skuId) use ($shopId): ?array {
                $sku = $this->catalog->find($skuId, $shopId);

                return $sku !== null ? ['sku_id' => $skuId, 'sku_code' => $sku['sku_code'], 'product_name' => $sku['product_name']] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function full(int $shopId): array
    {
        $brandsByType = DeviceBrandRecord::query()->orderBy('sort_order')->get()->groupBy('device_type_id');
        $tagsByType = DeviceProblemTagRecord::query()->orderBy('sort_order')->get()->groupBy('device_type_id');

        $tagIds = $tagsByType->flatten()->pluck('id')->all();
        $suggestedByTag = DeviceProblemSuggestedSkuRecord::query()
            ->whereIn('device_problem_tag_id', $tagIds)
            ->get()
            ->groupBy('device_problem_tag_id');

        return DeviceTypeRecord::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (DeviceTypeRecord $type) use ($brandsByType, $tagsByType, $suggestedByTag, $shopId): array {
                return [
                    'id' => $type->id,
                    'label' => $type->label,
                    'icon' => $type->icon,
                    'sort_order' => $type->sort_order,
                    'brands' => ($brandsByType->get($type->id) ?? collect())
                        ->map(static fn (DeviceBrandRecord $brand): array => [
                            'id' => $brand->id,
                            'name' => $brand->name,
                            'sort_order' => $brand->sort_order,
                        ])->values()->all(),
                    'problem_tags' => ($tagsByType->get($type->id) ?? collect())
                        ->map(function (DeviceProblemTagRecord $tag) use ($suggestedByTag, $shopId): array {
                            return [
                                'id' => $tag->id,
                                'label' => $tag->label,
                                'sort_order' => $tag->sort_order,
                                'suggested_parts' => ($suggestedByTag->get($tag->id) ?? collect())
                                    ->map(function (DeviceProblemSuggestedSkuRecord $suggested) use ($shopId): array {
                                        $sku = $this->catalog->find($suggested->sku_id, $shopId);

                                        return [
                                            'id' => $suggested->id,
                                            'sku_id' => $suggested->sku_id,
                                            'sku_code' => $sku['sku_code'] ?? null,
                                            'product_name' => $sku['product_name'] ?? null,
                                        ];
                                    })->values()->all(),
                            ];
                        })->values()->all(),
                ];
            })->all();
    }
}
