<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Persistence\Eloquent;

use Database\Factories\PaymentTransactionRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property string $method
 * @property int $amount_minor
 * @property string $status
 * @property string|null $provider_reference
 * @property int|null $confirmed_by_staff_id
 * @property Carbon|null $dispute_opened_at
 * @property string|null $dispute_proof_reference
 * @property int|null $dispute_resolved_by_staff_id
 */
final class PaymentTransactionRecord extends Model
{
    use HasFactory;

    protected $table = 'payments_transactions';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'method',
        'amount_minor',
        'status',
        'provider_reference',
        'confirmed_by_staff_id',
        'dispute_opened_at',
        'dispute_proof_reference',
        'dispute_resolved_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'dispute_opened_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return PaymentTransactionRecordFactory::new();
    }
}
