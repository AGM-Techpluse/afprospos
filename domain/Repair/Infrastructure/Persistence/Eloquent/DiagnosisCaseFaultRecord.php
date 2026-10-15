<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $diagnosis_case_id
 * @property int|null $device_problem_tag_id
 * @property int|null $common_component_id
 * @property string $label_snapshot
 * @property string|null $notes
 */
final class DiagnosisCaseFaultRecord extends Model
{
    protected $table = 'diagnosis_case_faults';

    protected $fillable = ['diagnosis_case_id', 'device_problem_tag_id', 'common_component_id', 'label_snapshot', 'notes'];
}
