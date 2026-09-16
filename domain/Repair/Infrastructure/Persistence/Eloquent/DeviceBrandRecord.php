<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $device_type_id
 * @property string $name
 * @property int $sort_order
 */
final class DeviceBrandRecord extends Model
{
    protected $table = 'device_brands';

    protected $fillable = ['device_type_id', 'name', 'sort_order'];
}
