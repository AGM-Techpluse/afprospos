<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\RepairJobRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $customer_id
 * @property string $device_make
 * @property string $device_model
 * @property int|null $technician_staff_id
 * @property int $labour_charge_minor
 * @property int|null $down_payment_required_minor
 * @property Carbon|null $down_payment_deadline_at
 * @property string $repair_status
 * @property string $financial_status
 * @property Carbon|null $estimated_collection_date
 * @property string|null $unrepairable_settlement_state
 * @property Carbon $created_at
 */
final class RepairJobRecord extends Model
{
    use HasFactory;

    protected $table = 'repair_jobs';

    protected $fillable = [
        'shop_id',
        'customer_id',
        'device_make',
        'device_model',
        'technician_staff_id',
        'labour_charge_minor',
        'down_payment_required_minor',
        'down_payment_deadline_at',
        'repair_status',
        'financial_status',
        'estimated_collection_date',
        'unrepairable_settlement_state',
    ];

    protected function casts(): array
    {
        return [
            'down_payment_deadline_at' => 'datetime',
            'estimated_collection_date' => 'date',
        ];
    }

    protected static function newFactory()
    {
        return RepairJobRecordFactory::new();
    }
}
