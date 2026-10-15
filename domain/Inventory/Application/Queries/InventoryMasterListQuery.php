<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;

/** Read side for the product form dropdowns and the Settings → Inventory catalog page. */
final class InventoryMasterListQuery
{
    public function __construct(private readonly InventoryMasterRepository $entries) {}

    /** @return array<string, array<int, array{id: int, kind: string, name: string, contact_phone: ?string, contact_email: ?string}>> */
    public function all(): array
    {
        return [
            'categories' => $this->entries->listByKind('category'),
            'units' => $this->entries->listByKind('unit'),
            'suppliers' => $this->entries->listByKind('supplier'),
        ];
    }
}
