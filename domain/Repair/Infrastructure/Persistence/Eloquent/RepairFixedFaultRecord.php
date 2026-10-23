<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property int|null $diagnosis_case_fault_id
 * @property int|null $device_problem_tag_id
 * @property int|null $common_component_id
 * @property string $label_snapshot
 * @property string|null $notes
 * @property int|null $commission_amount_minor
 * @property int|null $recorded_by_staff_id
 */
final class RepairFixedFaultRecord extends Model
{
    protected $table = 'repair_fixed_faults';

    protected $fillable = [
        'repair_job_id',
        'diagnosis_case_fault_id',
        'device_problem_tag_id',
        'common_component_id',
        'label_snapshot',
        'notes',
        'commission_amount_minor',
        'recorded_by_staff_id',
    ];
}
