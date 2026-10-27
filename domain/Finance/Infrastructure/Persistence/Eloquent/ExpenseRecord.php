<?php

declare(strict_types=1);

namespace Domain\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $shop_id
 * @property string $category
 * @property int $amount_minor
 * @property array<string, int>|null $allocation
 * @property string|null $note
 * @property int $recorded_by_staff_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExpenseRecord extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'shop_id',
        'category',
        'amount_minor',
        'allocation',
        'note',
        'recorded_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'allocation' => 'array',
        ];
    }
}
