<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryTransferRecord;

final class InventoryTransferQuery
{
    /**
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $status, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = InventoryTransferRecord::query()->with('sku.product')->latest('id');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($shopId !== null) {
            $query->where(function ($inner) use ($shopId): void {
                $inner->where('from_shop_id', $shopId)->orWhere('to_shop_id', $shopId);
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (InventoryTransferRecord $transfer): array => [
                'id' => $transfer->id,
                'sku_code' => $transfer->sku->sku_code,
                'brand' => $transfer->sku->product->brand,
                'model' => $transfer->sku->product->model,
                'inventory_item_id' => $transfer->inventory_item_id,
                'quantity' => $transfer->quantity,
                'from_shop_id' => $transfer->from_shop_id,
                'to_shop_id' => $transfer->to_shop_id,
                'status' => $transfer->status,
                'created_at' => $transfer->created_at?->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $transfer = InventoryTransferRecord::query()->with('sku.product')->find($id);

        if ($transfer === null) {
            return null;
        }

        return [
            'id' => $transfer->id,
            'sku_code' => $transfer->sku->sku_code,
            'brand' => $transfer->sku->product->brand,
            'model' => $transfer->sku->product->model,
            'inventory_item_id' => $transfer->inventory_item_id,
            'quantity' => $transfer->quantity,
            'from_shop_id' => $transfer->from_shop_id,
            'to_shop_id' => $transfer->to_shop_id,
            'initiated_by_staff_id' => $transfer->initiated_by_staff_id,
            'received_by_staff_id' => $transfer->received_by_staff_id,
            'status' => $transfer->status,
            'created_at' => $transfer->created_at?->toIso8601String(),
        ];
    }
}
