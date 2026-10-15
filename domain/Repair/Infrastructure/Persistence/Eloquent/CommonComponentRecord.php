<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $label
 * @property int $sort_order
 * @property int|null $commission_amount_minor
 */
final class CommonComponentRecord extends Model
{
    protected $table = 'common_components';

    protected $fillable = ['label', 'sort_order', 'commission_amount_minor'];
}
