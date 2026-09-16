<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

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
}
