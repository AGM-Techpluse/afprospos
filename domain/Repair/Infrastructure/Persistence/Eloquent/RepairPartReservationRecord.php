<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\RepairPartReservationRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property int|null $inventory_item_id
 * @property int $sku_id
 * @property int $quantity
 * @property string $status
 */
final class RepairPartReservationRecord extends Model
{
    use HasFactory;

    protected $table = 'repair_parts_reservations';

    protected $fillable = [
        'repair_job_id',
        'inventory_item_id',
        'sku_id',
        'quantity',
        'status',
    ];

    protected static function newFactory()
    {
        return RepairPartReservationRecordFactory::new();
    }
}
