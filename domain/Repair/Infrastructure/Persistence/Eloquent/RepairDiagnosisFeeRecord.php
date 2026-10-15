<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RepairDiagnosisFeeRecord extends Model
{
    protected $table = 'repair_diagnosis_fees';

    protected $fillable = ['repair_job_id', 'amount_minor', 'decision', 'set_by_staff_id', 'decided_by_staff_id', 'decided_at'];

    protected $casts = [
        'decided_at' => 'datetime',
    ];
}
