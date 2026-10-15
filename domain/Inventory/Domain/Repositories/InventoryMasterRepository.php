<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

/** Admin-managed lists that products are categorised, unit-ed and sourced by (categories, base units, suppliers). */
interface InventoryMasterRepository
{
    public const KINDS = ['category', 'unit', 'supplier'];

    public function nameExists(string $kind, string $name, ?int $exceptId = null): bool;

    public function exists(string $kind, string $name): bool;

    public function idOf(string $kind, string $name): ?int;

    public function create(string $kind, string $name, ?string $contactPhone, ?string $contactEmail): int;

    public function rename(int $id, string $name): void;

    public function find(int $id): ?array;

    public function delete(int $id): void;

    /** A category or unit is in use when a product carries its name; a supplier when a product points at its id. */
    public function isInUse(string $kind, int $id, string $name): bool;

    /** @return array<int, array{id: int, kind: string, name: string, contact_phone: ?string, contact_email: ?string}> */
    public function listByKind(string $kind): array;
}
