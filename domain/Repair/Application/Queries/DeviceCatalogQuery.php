<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemSuggestedSkuRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobProblemTagRecord;
use Illuminate\Support\Collection;

/** Backs both the Settings catalog-management page and the repair intake picker — same nested shape either way. */
final class DeviceCatalogQuery
{
    public function __construct(private readonly InventoryCatalogQuery $catalog) {}

    /**
     * Lightweight shape for the repair-intake picker — types, brands, and
     * models only (each model carrying its effective fixable-fault list),
     * skipping the suggested-SKU lookups `full()` does for the Settings
     * page (intake has no shop-scoped part data to show).
     *
     * @return array<int, array{id:int, label:string, icon:string, brands: array<int, array{id:int, name:string, models: array<int, array{id:int, name:string, fixable_faults: array<int, array{id:int, label:string}>, has_model_specific_faults: bool}>}>, problem_tags: array<int, array{id:int, label:string}>}>
     */
    public function structure(): array
    {
        $brandsByType = DeviceBrandRecord::query()->orderBy('sort_order')->get()->groupBy('device_type_id');
        $modelsByBrand = DeviceModelRecord::query()->orderBy('sort_order')->get()->groupBy('device_brand_id');
        [$tagsByType, $tagsByModel] = $this->problemTagsByTypeAndModel();

        return DeviceTypeRecord::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (DeviceTypeRecord $type) use ($brandsByType, $modelsByBrand, $tagsByType, $tagsByModel): array {
                $typeTags = $tagsByType->get($type->id) ?? collect();

                return [
                    'id' => $type->id,
                    'label' => $type->label,
                    'icon' => $type->icon,
                    'brands' => ($brandsByType->get($type->id) ?? collect())
                        ->map(fn (DeviceBrandRecord $brand): array => [
                            'id' => $brand->id,
                            'name' => $brand->name,
                            'models' => ($modelsByBrand->get($brand->id) ?? collect())
                                ->map(function (DeviceModelRecord $model) use ($typeTags, $tagsByModel): array {
                                    $modelTags = $tagsByModel->get($model->id) ?? collect();
                                    $effectiveTags = $modelTags->isNotEmpty() ? $modelTags : $typeTags;

                                    return [
                                        'id' => $model->id,
                                        'name' => $model->name,
                                        'fixable_faults' => $effectiveTags->map(static fn (DeviceProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label])->values()->all(),
                                        'has_model_specific_faults' => $modelTags->isNotEmpty(),
                                    ];
                                })
                                ->values()->all(),
                        ])
                        ->values()->all(),
                    'problem_tags' => $typeTags
                        ->map(static fn (DeviceProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label])
                        ->values()->all(),
                ];
            })->all();
    }

    /**
     * The effective fixable-fault list for one device model — its own
     * tags if it has any, else its device type's fallback tags. Backs
     * DiagnosisCasesController's "pick a revealed fault" selector, scoped
     * per diagnosis case by the case's own device_model_id.
     *
     * @return array<int, array{id:int, label:string}>
     */
    public function fixableFaultsForModel(int $deviceModelId): array
    {
        $model = DeviceModelRecord::query()->find($deviceModelId);

        if ($model === null) {
            return [];
        }

        $brand = DeviceBrandRecord::query()->find($model->device_brand_id);

        if ($brand === null) {
            return [];
        }

        $modelTags = DeviceProblemTagRecord::query()->where('device_model_id', $deviceModelId)->orderBy('sort_order')->get();
        $tags = $modelTags->isNotEmpty()
            ? $modelTags
            : DeviceProblemTagRecord::query()->whereNull('device_model_id')->where('device_type_id', $brand->device_type_id)->orderBy('sort_order')->get();

        return $tags->map(static fn (DeviceProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label])->values()->all();
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
        $modelsByBrand = DeviceModelRecord::query()->orderBy('sort_order')->get()->groupBy('device_brand_id');
        [$tagsByType, $tagsByModel] = $this->problemTagsByTypeAndModel();

        $allTagIds = $tagsByType->flatten()->pluck('id')->merge($tagsByModel->flatten()->pluck('id'))->all();
        $suggestedByTag = DeviceProblemSuggestedSkuRecord::query()
            ->whereIn('device_problem_tag_id', $allTagIds)
            ->get()
            ->groupBy('device_problem_tag_id');

        return DeviceTypeRecord::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (DeviceTypeRecord $type) use ($brandsByType, $modelsByBrand, $tagsByType, $tagsByModel, $suggestedByTag, $shopId): array {
                $typeTags = $tagsByType->get($type->id) ?? collect();

                return [
                    'id' => $type->id,
                    'label' => $type->label,
                    'icon' => $type->icon,
                    'sort_order' => $type->sort_order,
                    'brands' => ($brandsByType->get($type->id) ?? collect())
                        ->map(function (DeviceBrandRecord $brand) use ($modelsByBrand, $typeTags, $tagsByModel, $suggestedByTag, $shopId): array {
                            return [
                                'id' => $brand->id,
                                'name' => $brand->name,
                                'sort_order' => $brand->sort_order,
                                'models' => ($modelsByBrand->get($brand->id) ?? collect())
                                    ->map(function (DeviceModelRecord $model) use ($typeTags, $tagsByModel, $suggestedByTag, $shopId): array {
                                        $modelTags = $tagsByModel->get($model->id) ?? collect();

                                        return [
                                            'id' => $model->id,
                                            'name' => $model->name,
                                            'sort_order' => $model->sort_order,
                                            'has_model_specific_faults' => $modelTags->isNotEmpty(),
                                            'fixable_faults' => $this->mapTagsForSettings($modelTags->isNotEmpty() ? $modelTags : $typeTags, $suggestedByTag, $shopId),
                                        ];
                                    })->values()->all(),
                            ];
                        })->values()->all(),
                    'problem_tags' => $this->mapTagsForSettings($typeTags, $suggestedByTag, $shopId),
                ];
            })->all();
    }

    /** @return array{0: Collection<int, Collection<int, DeviceProblemTagRecord>>, 1: Collection<int, Collection<int, DeviceProblemTagRecord>>} */
    private function problemTagsByTypeAndModel(): array
    {
        $allTags = DeviceProblemTagRecord::query()->orderBy('sort_order')->get();

        return [
            $allTags->whereNull('device_model_id')->groupBy('device_type_id'),
            $allTags->whereNotNull('device_model_id')->groupBy('device_model_id'),
        ];
    }

    /** @param  Collection<int, DeviceProblemTagRecord>  $tags */
    private function mapTagsForSettings(Collection $tags, Collection $suggestedByTag, int $shopId): array
    {
        return $tags->map(function (DeviceProblemTagRecord $tag) use ($suggestedByTag, $shopId): array {
            return [
                'id' => $tag->id,
                'label' => $tag->label,
                'sort_order' => $tag->sort_order,
                'commission_amount_minor' => $tag->commission_amount_minor,
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
        })->values()->all();
    }
}
