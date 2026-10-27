<?php

declare(strict_types=1);

namespace Domain\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $shop_id
 * @property string $department
 * @property string $category
 * @property int $amount_minor
 * @property Carbon $expense_date
 * @property string $payment_method
 * @property string|null $proof_path
 * @property string|null $description
 * @property string $fund_type
 * @property int|null $reimbursed_to_staff_id
 * @property int $recorded_by_staff_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExpenseRecord extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'name',
        'shop_id',
        'department',
        'category',
        'amount_minor',
        'expense_date',
        'payment_method',
        'proof_path',
        'description',
        'fund_type',
        'reimbursed_to_staff_id',
        'recorded_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
        ];
    }
}
