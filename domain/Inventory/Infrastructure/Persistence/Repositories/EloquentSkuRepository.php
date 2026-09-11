<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Entities\Sku;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Domain\ValueObjects\Money;

final class EloquentSkuRepository implements SkuRepository
{
    public function get(SkuId $id): Sku
    {
        return $this->toDomain(SkuRecord::query()->findOrFail($id->value));
    }

    public function existsWithCode(string $skuCode): bool
    {
        return SkuRecord::query()->where('sku_code', $skuCode)->exists();
    }

    public function nextSequence(string $shopCode, string $category): int
    {
        $prefix = strtoupper($shopCode).'-'.strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category) ?? '', 0, 3)).'-';

        return SkuRecord::query()->where('sku_code', 'like', $prefix.'%')->count() + 1;
    }

    public function create(
        ProductId $productId,
        string $skuCode,
        array $attributes,
        bool $isSerialized,
        Money $costPrice,
        float $markupPercent,
        Money $sellingPrice,
        bool $sellingPriceOverridden,
        ?int $lowStockThreshold,
    ): SkuId {
        $record = SkuRecord::query()->create([
            'product_id' => $productId->value,
            'sku_code' => $skuCode,
            'attributes' => $attributes,
            'is_serialized' => $isSerialized,
            'cost_price_minor' => $costPrice->minor,
            'markup_percent' => $markupPercent,
            'selling_price_minor' => $sellingPrice->minor,
            'selling_price_overridden' => $sellingPriceOverridden,
            'low_stock_threshold' => $lowStockThreshold,
        ]);

        return new SkuId($record->id);
    }

    public function save(Sku $sku): void
    {
        SkuRecord::query()->whereKey($sku->id()->value)->update([
            'selling_price_minor' => $sku->sellingPrice()->minor,
            'selling_price_overridden' => $sku->sellingPriceOverridden(),
        ]);
    }

    private function toDomain(SkuRecord $record): Sku
    {
        return Sku::reconstitute(
            new SkuId($record->id),
            new ProductId($record->product_id),
            $record->sku_code,
            $record->attributes,
            $record->is_serialized,
            new Money($record->cost_price_minor),
            (float) $record->markup_percent,
            new Money($record->selling_price_minor),
            $record->selling_price_overridden,
            $record->low_stock_threshold,
        );
    }
}
