<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryMasterEntryRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Illuminate\Support\Facades\DB;

final class EloquentInventoryMasterRepository implements InventoryMasterRepository
{
    public function nameExists(string $kind, string $name, ?int $exceptId = null): bool
    {
        return InventoryMasterEntryRecord::query()
            ->where('kind', $kind)
            ->where('name', $name)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function exists(string $kind, string $name): bool
    {
        return InventoryMasterEntryRecord::query()->where('kind', $kind)->where('name', $name)->exists();
    }

    public function idOf(string $kind, string $name): ?int
    {
        return InventoryMasterEntryRecord::query()->where('kind', $kind)->where('name', $name)->value('id');
    }

    public function create(string $kind, string $name, ?string $contactPhone, ?string $contactEmail): int
    {
        return InventoryMasterEntryRecord::query()->create([
            'kind' => $kind,
            'name' => $name,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
        ])->id;
    }

    public function rename(int $id, string $name): void
    {
        $record = InventoryMasterEntryRecord::query()->findOrFail($id);
        $previous = $record->name;

        DB::transaction(function () use ($record, $previous, $name): void {
            $record->update(['name' => $name]);

            // Products carry the category or unit name, so they follow the rename.
            if ($record->kind === 'category') {
                ProductRecord::query()->where('category', $previous)->update(['category' => $name]);
            }

            if ($record->kind === 'unit') {
                ProductRecord::query()->where('unit', $previous)->update(['unit' => $name]);
            }
        });
    }

    public function find(int $id): ?array
    {
        $record = InventoryMasterEntryRecord::query()->find($id);

        return $record === null ? null : $this->toArray($record);
    }

    public function delete(int $id): void
    {
        InventoryMasterEntryRecord::query()->whereKey($id)->delete();
    }

    public function isInUse(string $kind, int $id, string $name): bool
    {
        return match ($kind) {
            'category' => ProductRecord::query()->where('category', $name)->exists(),
            'unit' => ProductRecord::query()->where('unit', $name)->exists(),
            'supplier' => ProductRecord::query()->where('supplier_id', $id)->exists(),
            default => false,
        };
    }

    public function listByKind(string $kind): array
    {
        return InventoryMasterEntryRecord::query()
            ->where('kind', $kind)
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryMasterEntryRecord $record): array => $this->toArray($record))
            ->all();
    }

    /** @return array{id: int, kind: string, name: string, contact_phone: ?string, contact_email: ?string} */
    private function toArray(InventoryMasterEntryRecord $record): array
    {
        return [
            'id' => $record->id,
            'kind' => $record->kind,
            'name' => $record->name,
            'contact_phone' => $record->contact_phone,
            'contact_email' => $record->contact_email,
        ];
    }
}
