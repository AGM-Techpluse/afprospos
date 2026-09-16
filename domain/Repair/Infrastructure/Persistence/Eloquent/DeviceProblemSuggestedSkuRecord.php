<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $device_problem_tag_id
 * @property int $sku_id
 */
final class DeviceProblemSuggestedSkuRecord extends Model
{
    protected $table = 'device_problem_suggested_skus';

    protected $fillable = ['device_problem_tag_id', 'sku_id'];
}
