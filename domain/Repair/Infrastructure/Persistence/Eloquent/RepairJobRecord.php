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
 * @property string|null $reported_issue
 * @property string|null $device_imei_serial
 * @property string $device_lock_type
 * @property string|null $device_lock_value
 * @property int|null $technician_staff_id
 * @property int $labour_charge_minor
 * @property int|null $down_payment_required_minor
 * @property Carbon|null $down_payment_deadline_at
 * @property string $repair_status
 * @property string $financial_status
 * @property Carbon|null $estimated_collection_date
 * @property string|null $unrepairable_settlement_state
 * @property string|null $resolution_notes
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
        'reported_issue',
        'device_imei_serial',
        'device_lock_type',
        'device_lock_value',
        'technician_staff_id',
        'labour_charge_minor',
        'down_payment_required_minor',
        'down_payment_deadline_at',
        'repair_status',
        'financial_status',
        'estimated_collection_date',
        'unrepairable_settlement_state',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'down_payment_deadline_at' => 'datetime',
            'estimated_collection_date' => 'date',
            // Customer's device passcode/pattern — encrypted at rest (APP_KEY),
            // transparently decrypted on read; RepairJobDetailQuery is what's
            // responsible for NOT handing the decrypted value to an unauthorized
            // viewer, this cast only protects it in the database.
            'device_lock_value' => 'encrypted',
        ];
    }

    protected static function newFactory()
    {
        return RepairJobRecordFactory::new();
    }
}
