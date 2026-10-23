<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $label
 * @property string $icon
 * @property int $sort_order
 */
final class DeviceTypeRecord extends Model
{
    protected $table = 'device_types';

    protected $fillable = ['label', 'icon', 'sort_order'];

    public function brands(): HasMany
    {
        return $this->hasMany(DeviceBrandRecord::class, 'device_type_id');
    }
}
