<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RepairQcCheckRecord extends Model
{
    protected $table = 'repair_qc_checks';

    protected $fillable = ['repair_job_id', 'outcome', 'notes', 'checked_by_staff_id'];
}
