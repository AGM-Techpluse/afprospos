<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\RepairDiagnosisRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property string $component
 * @property string $condition
 * @property string|null $notes
 * @property string|null $outcome
 * @property int $diagnosed_by_staff_id
 * @property Carbon $created_at
 */
final class RepairDiagnosisRecord extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'repair_diagnoses';

    protected $fillable = [
        'repair_job_id',
        'component',
        'condition',
        'notes',
        'outcome',
        'diagnosed_by_staff_id',
    ];

    protected static function newFactory()
    {
        return RepairDiagnosisRecordFactory::new();
    }
}
