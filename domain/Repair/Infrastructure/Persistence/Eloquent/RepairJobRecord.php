<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\RepairJobRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $diagnosis_case_id
 * @property int|null $reopened_from_repair_job_id
 * @property int $shop_id
 * @property int $customer_id
 * @property string|null $walk_in_name
 * @property string|null $walk_in_phone
 * @property string|null $walk_in_email
 * @property string $device_make
 * @property string $device_model
 * @property int|null $device_model_id
 * @property string|null $reported_issue
 * @property string|null $device_imei_serial
 * @property string $device_lock_type
 * @property string|null $device_lock_value
 * @property int|null $technician_staff_id
 * @property int|null $labour_charge_minor
 * @property int|null $repair_cost_minor
 * @property int|null $down_payment_required_minor
 * @property Carbon|null $down_payment_deadline_at
 * @property string $repair_status
 * @property string $financial_status
 * @property Carbon|null $estimated_collection_date
 * @property string|null $unrepairable_settlement_state
 * @property string|null $resolution_notes
 * @property int|null $failure_service_charge_minor
 * @property int|null $failure_refund_due_minor
 * @property Carbon $created_at
 */
final class RepairJobRecord extends Model
{
    use HasFactory;

    protected $table = 'repair_jobs';

    protected $fillable = [
        'shop_id',
        'diagnosis_case_id',
        'reopened_from_repair_job_id',
        'customer_id',
        'walk_in_name',
        'walk_in_phone',
        'walk_in_email',
        'device_make',
        'device_model',
        'device_model_id',
        'reported_issue',
        'device_imei_serial',
        'device_lock_type',
        'device_lock_value',
        'technician_staff_id',
        'labour_charge_minor',
        'repair_cost_minor',
        'down_payment_required_minor',
        'down_payment_deadline_at',
        'repair_status',
        'financial_status',
        'estimated_collection_date',
        'unrepairable_settlement_state',
        'resolution_notes',
        'failure_service_charge_minor',
        'failure_refund_due_minor',
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
