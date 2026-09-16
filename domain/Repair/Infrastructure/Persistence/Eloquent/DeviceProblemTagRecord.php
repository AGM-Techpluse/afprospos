<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $device_type_id
 * @property string $label
 * @property int $sort_order
 */
final class DeviceProblemTagRecord extends Model
{
    protected $table = 'device_problem_tags';

    protected $fillable = ['device_type_id', 'label', 'sort_order'];
}
