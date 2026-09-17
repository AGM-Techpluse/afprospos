<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Eloquent;

use Database\Factories\WarrantyClaimRecordFactory;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warranty_policy_id
 * @property int|null $originating_sale_id
 * @property int|null $originating_repair_job_id
 * @property int $customer_id
 * @property int|null $inventory_item_id
 * @property string $resolution_state
 * @property string|null $assessment_notes
 * @property string|null $selected_remedy
 * @property int|null $remedy_reference_id
 * @property Carbon $created_at
 */
final class WarrantyClaimRecord extends Model
{
    use HasFactory;

    protected $table = 'warranty_claims';

    protected $fillable = [
        'warranty_policy_id',
        'originating_sale_id',
        'originating_repair_job_id',
        'customer_id',
        'inventory_item_id',
        'resolution_state',
        'assessment_notes',
        'selected_remedy',
        'remedy_reference_id',
    ];

    /** `customers.id` is Shared Kernel (CPNC §0.2) — this relation is allowed; there is no equivalent relation to Sales/Repair's own tables. */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerRecord::class, 'customer_id');
    }

    protected static function newFactory(): WarrantyClaimRecordFactory
    {
        return WarrantyClaimRecordFactory::new();
    }
}
