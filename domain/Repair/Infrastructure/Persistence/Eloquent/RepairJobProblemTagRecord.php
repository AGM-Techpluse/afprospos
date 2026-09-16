<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property int|null $device_problem_tag_id
 * @property string $label_snapshot
 */
final class RepairJobProblemTagRecord extends Model
{
    protected $table = 'repair_job_problem_tags';

    protected $fillable = ['repair_job_id', 'device_problem_tag_id', 'label_snapshot'];
}
