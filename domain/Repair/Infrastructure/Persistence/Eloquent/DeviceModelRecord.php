<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $device_brand_id
 * @property string $name
 * @property int $sort_order
 */
final class DeviceModelRecord extends Model
{
    protected $table = 'device_models';

    protected $fillable = ['device_brand_id', 'name', 'sort_order'];
}
