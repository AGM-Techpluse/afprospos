<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\DiagnosisConclusionRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property string $root_cause
 * @property string $outcome
 * @property int $diagnosed_by_staff_id
 * @property Carbon $created_at
 */
final class DiagnosisConclusionRecord extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'repair_diagnosis_conclusions';

    protected $fillable = [
        'repair_job_id',
        'root_cause',
        'outcome',
        'diagnosed_by_staff_id',
    ];

    protected static function newFactory()
    {
        return DiagnosisConclusionRecordFactory::new();
    }
}
