<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $device_type_id
 * @property int|null $device_model_id
 * @property string $label
 * @property int $sort_order
 * @property int|null $commission_amount_minor
 */
final class DeviceProblemTagRecord extends Model
{
    protected $table = 'device_problem_tags';

    protected $fillable = ['device_type_id', 'device_model_id', 'label', 'sort_order', 'commission_amount_minor'];

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceTypeRecord::class, 'device_type_id');
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModelRecord::class, 'device_model_id');
    }
}
