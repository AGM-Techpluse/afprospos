<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Repair\Infrastructure\Persistence\Eloquent\CommonComponentRecord;

/** Backs the Settings catalog-management page and the diagnosis-case-intake quick-check card — same list either way. */
final class CommonComponentCatalogQuery
{
    /** @return array<int, array{id:int, label:string, sort_order:int, commission_amount_minor:int|null}> */
    public function list(): array
    {
        return CommonComponentRecord::query()
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (CommonComponentRecord $component): array => [
                'id' => $component->id,
                'label' => $component->label,
                'sort_order' => $component->sort_order,
                'commission_amount_minor' => $component->commission_amount_minor,
            ])
            ->all();
    }
}
