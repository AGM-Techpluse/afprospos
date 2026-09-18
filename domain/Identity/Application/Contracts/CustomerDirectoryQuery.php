<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Contracts;

/**
 * The published cross-module read contract for looking up a registered
 * customer by name/email/phone — Sales' checkout customer picker searches
 * through this rather than asking a cashier to type a raw `customers.id`
 * (an internal database key that should never be surfaced to or required
 * from a cashier), and never by reaching into Identity's own CustomerRecord
 * directly (same pattern as InventoryCatalogQuery for Inventory -> Sales).
 */
interface CustomerDirectoryQuery
{
    /**
     * @return array<int, array{id: int, name: string, email: ?string, phone: string}>
     */
    public function search(string $term, int $limit = 10): array;

    /**
     * Single-customer lookup — used to display the name/email of a customer
     * already attached to a checkout, without the caller touching CustomerRecord.
     *
     * `notification_preferences`/`marketing_opt_out` were added for
     * Notifications' NotificationEngine (NOTIF-BR-16/17) — additive, every
     * existing caller that only reads id/name/email/phone is unaffected.
     *
     * @return array{id: int, name: string, email: ?string, phone: string, notification_preferences: array<string, bool>, marketing_opt_out: bool}|null
     */
    public function find(int $id): ?array;

    /**
     * Batch lookup for a list/table page rendering many rows at once (e.g.
     * Sales/Repairs history), so a paginated page needs one query instead
     * of one `find()` per row.
     *
     * @param  int[]  $ids
     * @return array<int, array{id: int, name: string, email: ?string, phone: string, notification_preferences: array<string, bool>, marketing_opt_out: bool}> keyed by customer id
     */
    public function findMany(array $ids): array;
}
