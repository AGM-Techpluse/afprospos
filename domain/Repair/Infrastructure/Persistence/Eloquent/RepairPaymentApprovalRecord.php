<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Database\Factories\RepairPaymentApprovalRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property string $requested_financial_status
 * @property int $amount_minor
 * @property string $method
 * @property int $requested_by_staff_id
 * @property string $status
 * @property int|null $approved_by_staff_id
 * @property Carbon|null $decided_at
 */
final class RepairPaymentApprovalRecord extends Model
{
    use HasFactory;

    protected $table = 'repair_payment_approvals';

    protected $fillable = [
        'repair_job_id',
        'requested_financial_status',
        'amount_minor',
        'method',
        'requested_by_staff_id',
        'status',
        'approved_by_staff_id',
        'decided_at',
    ];

    protected $casts = ['decided_at' => 'datetime'];

    protected static function newFactory()
    {
        return RepairPaymentApprovalRecordFactory::new();
    }
}
