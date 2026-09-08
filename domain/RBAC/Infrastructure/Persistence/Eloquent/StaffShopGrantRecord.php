<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $staff_id
 * @property int $shop_id
 * @property ?int $granted_by_staff_id
 * @property Carbon $granted_at
 * @property ?Carbon $revoked_at
 * @property ?int $revoked_by_staff_id
 */
final class StaffShopGrantRecord extends Model
{
    protected $table = 'staff_shops';

    protected $fillable = [
        'staff_id',
        'shop_id',
        'granted_by_staff_id',
        'granted_at',
        'revoked_at',
        'revoked_by_staff_id',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
