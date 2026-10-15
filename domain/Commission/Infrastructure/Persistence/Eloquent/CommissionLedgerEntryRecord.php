<?php

declare(strict_types=1);

namespace Domain\Commission\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $staff_id
 * @property string $source_type
 * @property int $source_id
 * @property int $commissionable_amount_minor
 * @property float $rate_snapshot_percent
 * @property string $role_snapshot
 * @property string $entry_type
 * @property int|null $reversal_of_entry_id
 * @property string $status
 * @property int $shop_id
 */
final class CommissionLedgerEntryRecord extends Model
{
    protected $table = 'commission_ledger_entries';

    /** DBDD §17.1: append-only — deliberately no updated_at. */
    const UPDATED_AT = null;

    protected $fillable = [
        'staff_id', 'source_type', 'source_id', 'commissionable_amount_minor',
        'rate_snapshot_percent', 'role_snapshot', 'entry_type',
        'reversal_of_entry_id', 'status', 'shop_id',
    ];

    protected $casts = [
        'rate_snapshot_percent' => 'float',
    ];
}
