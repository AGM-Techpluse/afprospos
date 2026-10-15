<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RepairAgreementRecord extends Model
{
    protected $table = 'repair_agreements';

    protected $fillable = ['repair_job_id', 'note', 'recorded_by_staff_id'];
}
