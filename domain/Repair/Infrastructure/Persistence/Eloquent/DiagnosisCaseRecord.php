<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int|null $customer_id
 * @property int|null $device_model_id
 * @property int|null $diagnosis_technician_staff_id
 * @property int $created_by_staff_id
 * @property string $device_make
 * @property string $device_model
 * @property string|null $reported_issue
 * @property string|null $device_imei_serial
 * @property string $device_lock_type
 * @property string|null $device_lock_value
 * @property string $status
 * @property string|null $outcome
 * @property string|null $diagnosis_notes
 * @property int|null $diagnosis_fee_minor
 * @property Carbon|null $diagnosed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class DiagnosisCaseRecord extends Model
{
    protected $table = 'diagnosis_cases';

    protected $fillable = [
        'shop_id',
        'customer_id',
        'device_model_id',
        'diagnosis_technician_staff_id',
        'created_by_staff_id',
        'device_make',
        'device_model',
        'reported_issue',
        'device_imei_serial',
        'device_lock_type',
        'device_lock_value',
        'status',
        'outcome',
        'diagnosis_notes',
        'diagnosis_fee_minor',
        'diagnosed_at',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_at' => 'datetime',
            'device_lock_value' => 'encrypted',
        ];
    }
}
